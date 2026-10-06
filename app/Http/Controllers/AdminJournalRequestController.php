<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalRequest;
use App\Services\AuditLog;
use App\Services\RatingService;
use App\Services\Verification\VerificationAdapter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin journal-request resolution: link an existing journal or create one inline, then re-verify the article. */
class AdminJournalRequestController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.journal-requests', [
            'requests' => JournalRequest::query()->where('status', 'open')->with(['article.user', 'user'])->latest()->paginate(25),
            'fields' => config('uniscience.fields'),
            'tiers' => array_keys(config('uniscience.tiers')),
            'sources' => Journal::SOURCES,
        ]);
    }

    public function resolve(Request $request, JournalRequest $journalRequest, VerificationAdapter $verifier): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($journalRequest->status === 'open', 422);

        $journal = null;
        if ($request->filled('journal_id')) {
            $request->validate(['journal_id' => ['required', 'integer', 'exists:journals,id']]);
            $journal = Journal::findOrFail($request->integer('journal_id'));
        } else {
            $data = $request->validate([
                'new_name' => ['required', 'string', 'max:255'],
                'new_issn' => ['nullable', 'regex:/^\d{4}-\d{3}[\dXx]$/'],
                'new_field' => ['required', 'array', 'min:1'],
                'new_field.*' => ['string', Rule::in(config('uniscience.fields'))],
                'new_tier' => ['required', Rule::in(array_keys(config('uniscience.tiers')))],
                'new_listed_from' => ['required', 'date'],
            ], ['new_issn.regex' => 'ISSN formati noto‘g‘ri (0000-0000).']);
            $issn = ($data['new_issn'] ?? null) ?: null;
            $journal = Journal::create([
                'name' => $data['new_name'],
                'issn' => $issn,
                'field' => collect($data['new_field'])->unique()->implode('; '),
                'tier' => $data['new_tier'],
                'listed_from' => $data['new_listed_from'],
                'source' => Journal::sourceForTier($data['new_tier']),
            ]);
            AuditLog::record('journal.created', $journal, null, $journal->only(['issn', 'name', 'field', 'tier', 'listed_from', 'source']));
        }

        $article = $journalRequest->article;
        $oldRequest = $journalRequest->only(['status', 'resolved_journal_id', 'note']);
        $oldArticle = $article->only(['status', 'reason', 'journal_id']);
        $article->update(['journal_name' => $journal->name, 'issn' => $article->issn ?: $journal->issn]);

        $result = $verifier->verify($article->fresh(), $journal);
        $article->update([
            'journal_id' => $journal->id,
            'status' => $result->status,
            'reason' => $result->reason,
            'decided_at' => in_array($result->status, ['approved', 'rejected'], true) ? now() : null,
            'decided_by' => auth()->id(),
        ]);
        $journalRequest->update([
            'status' => 'resolved',
            'resolved_journal_id' => $journal->id,
            'note' => 'Jurnal biriktirildi: '.$journal->name,
        ]);
        AuditLog::record('journal_request.resolved', $journalRequest, $oldRequest, $journalRequest->fresh()->only(['status', 'resolved_journal_id', 'note']));
        AuditLog::record('article.review_decided', $article, $oldArticle, $article->fresh()->only(['status', 'reason', 'journal_id']));
        RatingService::refreshAfterChange($article->user);

        return back()->with('ok', 'Ariza hal qilindi: '.$result->reason);
    }

    public function reject(Request $request, JournalRequest $journalRequest): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($journalRequest->status === 'open', 422);
        $data = $request->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Rad etish sababini kiriting.']);

        $old = $journalRequest->only(['status', 'note']);
        $journalRequest->update(['status' => 'rejected', 'note' => $data['note']]);
        AuditLog::record('journal_request.rejected', $journalRequest, $old, $journalRequest->fresh()->only(['status', 'note']));

        return back()->with('ok', 'Ariza rad etildi.');
    }
}
