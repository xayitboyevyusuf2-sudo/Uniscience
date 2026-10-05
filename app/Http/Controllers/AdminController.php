<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserApprovalRequest;
use App\Models\Article;
use App\Models\Journal;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AccountApproved;
use App\Notifications\ArticleStatus;
use App\Services\JournalImporter;
use App\Services\Verifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $q->whereHas('user', fn ($x) => $x->where('faculty', $u->faculty));
        }
        $queue = $q->get();
        $suggest = [];
        foreach ($queue as $a) {
            if (! $a->journal_id) {
                $n = Verifier::norm($a->journal_name);
                $suggest[$a->id] = $n === '' ? collect() : Journal::where('name_norm', 'like', '%'.$n.'%')->limit(5)->get();
            }
        }

        return view('admin', ['queue' => $queue, 'suggest' => $suggest, 'all' => count($suggest) ? Journal::orderBy('name')->get(['id', 'name']) : collect(),
            'journals' => Journal::orderBy('name')->paginate(25), 'users' => User::where('approval_status', 'pending')->latest()->get()->concat(User::where('approval_status', '!=', 'pending')->latest()->take(30)->get()),
            'limit' => Setting::get('yearly_limit', config('uniscience.yearly_limit')), 'log' => DB::table('setting_changes')->latest('id')->take(5)->get()]);
    }

    public function approveUser(UserApprovalRequest $r, User $user)
    {
        abort_unless($user->approval_status === 'pending', 422);
        $approved = $r->validated()['decision'] === 'approve';
        $user->update(['approval_status' => $approved ? 'approved' : 'rejected']);
        if ($approved) {
            try {
                $user->notify(new AccountApproved);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('ok', $approved ? 'Foydalanuvchi tasdiqlandi.' : 'Foydalanuvchi rad etildi.');
    }

    public function decide(Request $r, Article $article)
    {
        $this->guard();
        $u = auth()->user();
        abort_unless($u->role === 'admin' || $article->user->faculty === $u->faculty, 403);
        abort_unless($article->status === 'manual', 422);
        $r->validate(['decision' => 'required|in:approve,reject', 'note' => 'required_if:decision,reject|nullable|string|max:500']);
        $ok = $r->decision === 'approve';
        $j = $article->journal;
        if ($ok && ! $j) { // moderator matches the journal ("12 · Name"); only the leading id is used
            $j = Journal::find((int) $r->journal_pick);
            if (! $j) {
                return back()->withErrors(['journal_pick' => 'Tasdiqlash uchun jurnalni tanlang.']);
            }
        }
        $article->update(['status' => $ok ? 'approved' : 'rejected', 'reason' => $ok ? 'Tasdiqlandi (moderator)' : 'Rad etildi: '.$r->note, 'journal_id' => $ok ? $j->id : $article->journal_id]);
        if ($ok && $article->issn && ! $j->issn && ! Journal::where('issn', $article->issn)->exists()) {
            $j->update(['issn' => $article->issn]);
        } // the list learns ISSNs
        DB::table('review_logs')->insert(['article_id' => $article->id, 'user_id' => $u->id, 'decision' => $ok ? 'approved' : 'rejected', 'note' => $r->note, 'created_at' => now(), 'updated_at' => now()]);
        try {
            $article->user->notify(new ArticleStatus($article->fresh()));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('ok', 'Qaror saqlandi');
    }

    public function import(Request $r) // ISSN optional; never deletes
    {$this->guard(['admin']);
        $r->validate(['csv' => 'required|file|mimes:csv,txt']);
        set_time_limit(300);

        return back()->with('ok', JournalImporter::fromFile($r->file('csv')->getRealPath()).' ta yozuv import qilindi');
    }

    public function settings(Request $r)
    {
        $this->guard(['admin']);
        $r->validate(['yearly_limit' => 'required|integer|min:1|max:50']);
        Setting::put('yearly_limit', $r->yearly_limit, auth()->id());

        return back()->with('ok', 'Saqlandi');
    }

    public function role(Request $r, User $user)
    {
        $this->guard(['admin']);
        $r->validate(['role' => 'required|in:student,moderator,admin']);
        $user->update(['role' => $r->role, 'blocked' => $r->boolean('blocked')]);

        return back()->with('ok','Yangilandi');
    }
}
