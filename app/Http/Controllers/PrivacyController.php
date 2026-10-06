<?php

namespace App\Http\Controllers;

use App\Models\DataDeletionRequest;
use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** O‘RQ-547: privacy page, user deletion requests, and admin processing with anonymization. */
class PrivacyController extends Controller
{
    public function show(): View
    {
        return view('privacy');
    }

    public function requestDeletion(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (DataDeletionRequest::where('user_id', $user->id)->where('status', 'open')->exists()) {
            return back()->withErrors(['deletion' => 'Ochiq o‘chirish so‘rovingiz allaqachon mavjud.']);
        }
        $deletion = DataDeletionRequest::create(['user_id' => $user->id]);
        AuditLog::record('user.deletion_requested', $user, null, ['request_id' => $deletion->id]);

        return back()->with('ok', 'O‘chirish so‘rovi yuborildi. Administrator ko‘rib chiqadi.');
    }

    public function adminIndex(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.deletion-requests', ['requests' => DataDeletionRequest::with('user')->latest()->paginate(25)]);
    }

    public function process(Request $request, DataDeletionRequest $deletionRequest): RedirectResponse
    {
        $admin = auth()->user();
        abort_unless($admin->isAdmin(), 403);
        abort_unless($deletionRequest->status === 'open', 422);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['done', 'rejected'])],
            'note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:500'],
        ], ['note.required_if' => 'Rad etish sababini kiriting.']);

        $user = $deletionRequest->user;
        $old = $user->only(['name', 'first_name', 'last_name', 'patronymic', 'email', 'username', 'blocked']);

        DB::transaction(function () use ($deletionRequest, $user, $data, $admin): void {
            if ($data['decision'] === 'done') {
                $anonEmail = 'deleted-'.$user->id.'@anon.local';
                $photoPath = $user->photo_path;
                $user->update([
                    'name' => 'O‘chirilgan foydalanuvchi',
                    'first_name' => 'O‘chirilgan',
                    'last_name' => 'foydalanuvchi',
                    'patronymic' => null,
                    'username' => 'deleted-'.$user->id,
                    'email' => $anonEmail,
                    'blocked' => true,
                    'photo_path' => null,
                    'bio' => null,
                    'interests' => null,
                    'phone' => null,
                    'telegram' => null,
                    'birth_date' => null,
                    'gpa' => null,
                    'student_id' => null,
                ]);
                // notifications and sessions are removed; articles stay for statistics
                DB::table('notifications')->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                foreach ($user->articles()->whereNotNull('pdf_path')->pluck('pdf_path') as $path) {
                    Storage::disk('local')->delete($path);
                }
                if ($photoPath) {
                    Storage::disk('local')->delete($photoPath);
                }
            }
            $deletionRequest->update([
                'status' => $data['decision'],
                'note' => $data['note'] ?? null,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);
        });
        AuditLog::record('user.deletion_processed', $user, $old, $user->fresh()->only(['name', 'email', 'blocked']) + ['decision' => $data['decision']]);

        return back()->with('ok', 'So‘rov ko‘rib chiqildi.');
    }
}
