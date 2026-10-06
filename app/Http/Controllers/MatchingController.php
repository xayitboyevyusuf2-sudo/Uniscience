<?php

namespace App\Http\Controllers;

use App\Models\MentorRequest;
use App\Models\TimeSlot;
use App\Models\User;
use App\Notifications\MentorRequestAnswered;
use App\Notifications\MentorRequestReceived;
use App\Services\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mentor matching: mentors publish free slots, students request them, mentors answer.
 * Mentor = category professor/tadqiqotchi with approval_status='approved' (T4).
 * Request senders: bakalavr, magistr, tadqiqotchi.
 */
class MatchingController extends Controller
{
    public const MAX_ACTIVE_REQUESTS = 3;

    public function index(Request $request): View
    {
        $this->guardRequester();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'faculty' => ['nullable', 'string', 'max:190'],
            'department' => ['nullable', 'string', 'max:190'],
        ]);

        $mentors = User::query()->whereIn('category', ['professor', 'tadqiqotchi'])->where('approval_status', 'approved')
            ->when($filters['faculty'] ?? null, fn ($q, $f) => $q->where('faculty', $f))
            ->when($filters['department'] ?? null, fn ($q, $d) => $q->where('department', $d))
            ->when(trim((string) ($filters['q'] ?? '')), function ($q) use ($filters): void {
                $term = trim((string) $filters['q']);
                $q->where(fn ($x) => $x->where('name', 'like', '%'.$term.'%')->orWhere('first_name', 'like', '%'.$term.'%')->orWhere('last_name', 'like', '%'.$term.'%'));
            })
            ->with(['slots' => fn ($q) => $q->whereDate('slot_date', '>=', now()->toDateString())->orderBy('slot_date')->orderBy('start_time')])
            ->orderBy('last_name')->orderBy('first_name')->get();

        return view('matching.index', [
            'mentors' => $mentors,
            'filters' => $filters,
            'activeRequests' => $this->activeRequestCount($request->user()),
            'maxActive' => self::MAX_ACTIVE_REQUESTS,
        ]);
    }

    public function storeRequest(Request $request, TimeSlot $slot): RedirectResponse
    {
        $this->guardRequester();
        $student = $request->user();
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);

        if ($slot->user_id === $student->id) {
            throw ValidationException::withMessages(['slot' => 'O‘z slotingizga so‘rov yuborib bo‘lmaydi.']);
        }
        if ($slot->slot_date->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['slot' => 'O‘tgan sanadagi slotga so‘rov yuborib bo‘lmaydi.']);
        }
        if (MentorRequest::where('student_id', $student->id)->where('slot_id', $slot->id)->exists()) {
            throw ValidationException::withMessages(['slot' => 'Bu slotga allaqachon so‘rov yuborgansiz.']);
        }
        if ($this->activeRequestCount($student) >= self::MAX_ACTIVE_REQUESTS) {
            throw ValidationException::withMessages(['slot' => 'Eng ko‘pi bilan '.self::MAX_ACTIVE_REQUESTS.' ta faol so‘rov bo‘lishi mumkin.']);
        }

        $mentorRequest = DB::transaction(fn () => MentorRequest::create([
            'student_id' => $student->id,
            'slot_id' => $slot->id,
            'message' => $data['message'],
        ]));
        try {
            $slot->mentor->notify(new MentorRequestReceived($mentorRequest));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('ok', 'So‘rov yuborildi.');
    }

    public function mySlots(): View
    {
        $mentor = $this->guardMentor();

        return view('matching.slots', ['slots' => $mentor->slots()->withCount(['requests', 'acceptedRequests'])->orderBy('slot_date')->orderBy('start_time')->get()]);
    }

    public function storeSlot(Request $request): RedirectResponse
    {
        $mentor = $this->guardMentor();
        $data = $this->validateSlot($request);
        $this->guardOverlap($mentor, $data);

        $slot = $mentor->slots()->create($data);
        AuditLog::record('slot.created', $slot, null, $slot->only(['slot_date', 'start_time', 'end_time', 'location']));

        return back()->with('ok', 'Slot qo‘shildi.');
    }

    public function updateSlot(Request $request, TimeSlot $slot): RedirectResponse
    {
        $mentor = $this->guardMentor();
        abort_unless($slot->user_id === $mentor->id, 403);
        $data = $this->validateSlot($request);
        $this->guardOverlap($mentor, $data, $slot->id);

        $old = $slot->only(['slot_date', 'start_time', 'end_time', 'location', 'note']);
        $slot->update($data);
        AuditLog::record('slot.updated', $slot, $old, $slot->fresh()->only(['slot_date', 'start_time', 'end_time', 'location', 'note']));

        return back()->with('ok', 'Slot yangilandi.');
    }

    public function destroySlot(TimeSlot $slot): RedirectResponse
    {
        $mentor = $this->guardMentor();
        abort_unless($slot->user_id === $mentor->id, 403);

        $old = $slot->only(['slot_date', 'start_time', 'end_time', 'location']);
        $pendingOrAccepted = $slot->requests()->whereIn('status', ['pending', 'accepted'])->with('student')->get();
        DB::transaction(function () use ($slot, $pendingOrAccepted): void {
            foreach ($pendingOrAccepted as $mentorRequest) {
                $mentorRequest->update(['status' => 'cancelled', 'responded_at' => now()]);
                try {
                    $mentorRequest->student->notify(new MentorRequestAnswered($mentorRequest));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
            $slot->delete();
        });
        AuditLog::record('slot.deleted', null, $old + ['cancelled_requests' => $pendingOrAccepted->count()], null);

        return back()->with('ok', 'Slot o‘chirildi; faol so‘rovlar bekor qilindi.');
    }

    public function requestsInbox(): View
    {
        $mentor = $this->guardMentor();
        $requests = MentorRequest::query()->whereHas('slot', fn ($q) => $q->where('user_id', $mentor->id))
            ->with(['student.rating', 'slot'])->where('status', 'pending')->oldest()->get();

        return view('matching.requests', ['requests' => $requests]);
    }

    public function respond(Request $request, MentorRequest $mentorRequest): RedirectResponse
    {
        $mentor = $this->guardMentor();
        abort_unless($mentorRequest->slot->user_id === $mentor->id, 403); // policy: only the slot owner answers
        abort_unless($mentorRequest->status === 'pending', 422);
        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'response_note' => ['nullable', 'string', 'max:500'],
        ]);

        $mentorRequest->update([
            'status' => $data['decision'] === 'accept' ? 'accepted' : 'rejected',
            'response_note' => $data['response_note'] ?? null,
            'responded_at' => now(),
        ]);
        try {
            $mentorRequest->student->notify(new MentorRequestAnswered($mentorRequest));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('ok', 'Javob saqlandi.');
    }

    public function cancel(MentorRequest $mentorRequest): RedirectResponse
    {
        $student = $request_user = auth()->user();
        abort_unless($mentorRequest->student_id === $student->id, 403); // policy: only the request owner cancels
        abort_unless(in_array($mentorRequest->status, ['pending', 'accepted'], true), 422);

        $mentorRequest->update(['status' => 'cancelled', 'responded_at' => now()]);

        return back()->with('ok', 'So‘rov bekor qilindi.');
    }

    private function guardMentor(): User
    {
        $user = auth()->user();
        abort_unless($user->isMentor() && $user->approval_status === 'approved', 403);

        return $user;
    }

    private function guardRequester(): void
    {
        $user = auth()->user();
        abort_unless(in_array($user->category, ['bakalavr', 'magistr', 'tadqiqotchi'], true), 403);
    }

    /** Active = pending OR accepted for a future slot. */
    private function activeRequestCount(User $student): int
    {
        return MentorRequest::where('student_id', $student->id)
            ->where(fn ($q) => $q->where('status', 'pending')
                ->orWhere(fn ($x) => $x->where('status', 'accepted')->whereHas('slot', fn ($s) => $s->whereDate('slot_date', '>=', now()->toDateString()))))
            ->count();
    }

    /** @return array<string, mixed> */
    private function validateSlot(Request $request): array
    {
        return $request->validate([
            'slot_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['required', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:190'],
        ], [
            'slot_date.after_or_equal' => 'Slot sanasi bugun yoki kelajakdagi sana bo‘lishi kerak.',
            'end_time.after' => 'Tugash vaqti boshlanishdan keyin bo‘lishi kerak.',
        ]);
    }

    private function guardOverlap(User $mentor, array $data, ?int $exceptId = null): void
    {
        $overlap = $mentor->slots()->whereDate('slot_date', $data['slot_date'])
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where(fn ($q) => $q->where('start_time', '<', $data['end_time'])->where('end_time', '>', $data['start_time']))
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_time' => 'Bu vaqt shu kundagi boshqa slot bilan kesishadi.']);
        }
    }
}
