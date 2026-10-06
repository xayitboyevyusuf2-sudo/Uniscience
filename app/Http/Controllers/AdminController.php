<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserApprovalRequest;
use App\Models\Article;
use App\Models\Journal;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AccountApproved;
use App\Services\ArticleDecisionService;
use App\Services\AuditLog;
use App\Services\JournalImporter;
use App\Services\Verifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function guard($roles = ['admin', 'moderator'])
    {
        abort_unless(in_array(auth()->user()->role, $roles), 403);
    }

    public function index()
    {
        $this->guard();
        $u = auth()->user();
        $q = Article::with(['user', 'journal'])->where('status', 'manual');
        if ($u->role === 'moderator') {
            $faculties = $u->moderatorFacultyNames();
            $q->whereHas('user', fn ($x) => $x->whereIn('faculty', $faculties));
        }
        $queue = $q->get();
        $suggest = [];
        foreach ($queue as $a) {
            if (! $a->journal_id) {
                $n = Verifier::norm($a->journal_name);
                $suggest[$a->id] = $n === '' ? collect() : Journal::where('name_norm', 'like', '%'.$n.'%')->limit(5)->get();
            }
        }

        $users = User::where('approval_status', 'pending')->latest()->get()->concat(User::where('approval_status', '!=', 'pending')->latest()->take(30)->get());
        $facultyAssignments = DB::table('moderator_faculties')->get()->groupBy('user_id')->map(fn ($rows) => $rows->pluck('faculty')->all());
        $facultyOptions = User::query()->whereNotNull('faculty')->where('faculty', '!=', '')->distinct()->orderBy('faculty')->pluck('faculty');

        return view('admin', ['queue' => $queue, 'suggest' => $suggest, 'all' => count($suggest) ? Journal::orderBy('name')->get(['id', 'name']) : collect(),
            'journals' => Journal::orderBy('name')->paginate(25), 'users' => $users, 'facultyAssignments' => $facultyAssignments, 'facultyOptions' => $facultyOptions,
            'limit' => Setting::get('yearly_limit', config('uniscience.yearly_limit')), 'log' => DB::table('setting_changes')->latest('id')->take(5)->get()]);
    }

    public function audit(Request $request): View
    {
        $this->guard(['admin']);
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:190'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], [
            'user_id.integer' => 'Foydalanuvchi filtri noto‘g‘ri.',
            'user_id.exists' => 'Tanlangan foydalanuvchi topilmadi.',
            'action.max' => 'Amal filtri juda uzun.',
            'from.date' => 'Boshlanish sanasi noto‘g‘ri.',
            'to.date' => 'Tugash sanasi noto‘g‘ri.',
            'to.after_or_equal' => 'Tugash sanasi boshlanish sanasidan oldin bo‘lishi mumkin emas.',
        ]);

        $query = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('audit_logs.*', 'users.name as actor_name', 'users.email as actor_email');

        if (! empty($filters['user_id'])) {
            $query->where('audit_logs.user_id', $filters['user_id']);
        }
        if (! empty($filters['action'])) {
            $query->where('audit_logs.action', 'like', '%'.$filters['action'].'%');
        }
        if (! empty($filters['from'])) {
            $query->whereDate('audit_logs.created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('audit_logs.created_at', '<=', $filters['to']);
        }

        return view('admin.audit', [
            'logs' => $query->orderByDesc('audit_logs.created_at')->orderByDesc('audit_logs.id')->paginate(25)->withQueryString(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $filters,
        ]);
    }

    public function approveUser(UserApprovalRequest $r, User $user)
    {
        abort_unless($user->approval_status === 'pending', 422);
        $approved = $r->validated()['decision'] === 'approve';
        $old = ['approval_status' => $user->approval_status];
        $user->update(['approval_status' => $approved ? 'approved' : 'rejected']);
        AuditLog::record('user.approval_decided', $user, $old, ['approval_status' => $user->approval_status]);
        if ($approved) {
            try {
                $user->notify(new AccountApproved);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('ok', $approved ? 'Foydalanuvchi tasdiqlandi.' : 'Foydalanuvchi rad etildi.');
    }

    public function decide(Request $r, Article $article, ArticleDecisionService $decisionService)
    {
        $this->guard();
        $u = auth()->user();
        abort_unless($u->isAdmin() || $u->canReviewFaculty($article->user->faculty), 403);
        abort_unless($article->status === 'manual', 422);
        $r->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
            'field' => ['nullable', Rule::in(config('uniscience.fields'))],
            'pdf_title_matches' => ['nullable', 'boolean'],
            'author_positions_checked' => ['nullable', 'boolean'],
        ]);

        $error = $decisionService->decide(
            $article,
            $u,
            $r->decision,
            $r->note,
            $r->journal_pick,
            $r->field,
            [
                'pdf_title_matches' => $r->boolean('pdf_title_matches'),
                'author_positions_checked' => $r->boolean('author_positions_checked'),
            ],
        );

        if ($error === 'journal_pick') {
            return back()->withErrors(['journal_pick' => 'Tasdiqlash uchun jurnalni tanlang.']);
        }
        if ($error === 'field') {
            return back()->withErrors(['field' => 'Tasdiqlash uchun nashr sohasini tanlang.']);
        }

        return back()->with('ok', 'Qaror saqlandi');
    }

    public function import(Request $r) // ISSN optional; never deletes
    {
        $this->guard(['admin']);
        $r->validate(['csv' => 'required|file|mimes:csv,txt']);
        set_time_limit(300);
        $imported = JournalImporter::fromFile($r->file('csv')->getRealPath());
        AuditLog::record('journals.imported', null, null, ['count' => $imported]);

        return back()->with('ok', $imported.' ta yozuv import qilindi');
    }

    public function settings(Request $r)
    {
        $this->guard(['admin']);
        $r->validate(['yearly_limit' => 'required|integer|min:1|max:50']);
        $oldLimit = Setting::get('yearly_limit', config('uniscience.yearly_limit'));
        Setting::put('yearly_limit', $r->yearly_limit, auth()->id());
        AuditLog::record('settings.yearly_limit_updated', null, ['yearly_limit' => $oldLimit], ['yearly_limit' => (int) $r->yearly_limit]);

        return back()->with('ok', 'Saqlandi');
    }

    public function role(Request $r, User $user)
    {
        $this->guard(['admin']);
        $facultyOptions = User::query()->whereNotNull('faculty')->where('faculty', '!=', '')->distinct()->pluck('faculty')->all();
        $data = $r->validate([
            'role' => ['required', 'in:student,moderator,admin'],
            'faculty_ids' => [Rule::requiredIf($r->input('role') === 'moderator'), 'array', 'min:1'],
            'faculty_ids.*' => ['string', 'max:120', Rule::in($facultyOptions)],
        ], [
            'faculty_ids.required' => 'Moderatorga kamida bitta fakultet biriktiring.',
            'faculty_ids.min' => 'Moderatorga kamida bitta fakultet biriktiring.',
            'faculty_ids.*.in' => 'Tanlangan fakultet topilmadi.',
        ]);
        $old = $user->only(['role', 'blocked', 'faculty']);
        $old['moderator_faculties'] = DB::table('moderator_faculties')->where('user_id', $user->id)->pluck('faculty')->all();
        $facultyIds = array_values(array_unique($data['faculty_ids'] ?? []));

        DB::transaction(function () use ($user, $r, $data, $facultyIds): void {
            $user->update([
                'role' => $data['role'],
                'blocked' => $r->boolean('blocked'),
                'faculty' => $data['role'] === 'moderator' ? ($facultyIds[0] ?? null) : $user->faculty,
            ]);
            DB::table('moderator_faculties')->where('user_id', $user->id)->delete();
            if ($data['role'] === 'moderator') {
                foreach ($facultyIds as $faculty) {
                    DB::table('moderator_faculties')->insert(['user_id' => $user->id, 'faculty' => $faculty]);
                }
            }
        });

        $new = $user->fresh()->only(['role', 'blocked', 'faculty']);
        $new['moderator_faculties'] = DB::table('moderator_faculties')->where('user_id', $user->id)->pluck('faculty')->all();
        AuditLog::record('user.role_or_block_updated', $user, $old, $new);

        return back()->with('ok', 'Yangilandi');
    }
}
