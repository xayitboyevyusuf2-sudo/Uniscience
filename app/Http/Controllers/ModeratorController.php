<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ConferenceCertificate;
use App\Models\Journal;
use App\Models\JournalRequest;
use App\Models\User;
use App\Services\AuditLog;
use App\Services\Verifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ModeratorController extends Controller
{
    public function queue(): View
    {
        $reviewer = $this->reviewer();
        $faculties = $reviewer->isAdmin() ? null : $reviewer->moderatorFacultyNames();

        $articles = Article::query()->with(['user', 'journal', 'conferenceCertificate'])->where('status', 'manual');
        $certificates = ConferenceCertificate::query()->with(['article.user', 'article.journal'])->where('status', 'pending');
        $journalRequests = JournalRequest::query()->with(['article.user', 'article.journal'])->where('status', 'open');

        if (! $reviewer->isAdmin()) {
            $articles->whereHas('user', fn ($query) => $query->whereIn('faculty', $faculties));
            $certificates->whereHas('article.user', fn ($query) => $query->whereIn('faculty', $faculties));
            $journalRequests->whereHas('article.user', fn ($query) => $query->whereIn('faculty', $faculties));
        }

        return view('moderator.queue', [
            'articles' => $articles->latest()->get(),
            'certificates' => $certificates->latest()->get(),
            'journalRequests' => $journalRequests->latest()->get(),
        ]);
    }

    public function show(Article $article): View
    {
        $reviewer = $this->reviewer();
        $this->authorizeArticleReview($reviewer, $article);
        $article->load(['user', 'journal', 'authors', 'conferenceCertificate']);
        $articleType = config('uniscience.article_types.'.$article->type, []);
        $isJournalType = ($articleType['family'] ?? 'journal') === 'journal';
        $journalCandidate = $article->journal;
        $journalSuggestions = collect();

        if ($isJournalType && ! $journalCandidate) {
            $journalCandidate = $article->issn
                ? Journal::where('issn', $article->issn)->first()
                : Journal::where('name_norm', Verifier::norm($article->journal_name))->first();
        }
        if ($isJournalType && ! $article->journal_id) {
            $normalizedName = Verifier::norm($article->journal_name);
            $journalSuggestions = $normalizedName === ''
                ? collect()
                : Journal::where('name_norm', 'like', '%'.$normalizedName.'%')->orderBy('name')->limit(10)->get(['id', 'name', 'issn', 'tier']);
        }

        $journalListedAtPublication = $journalCandidate
            && $journalCandidate->listed_from->lte($article->published_at)
            && (! $journalCandidate->listed_to || $journalCandidate->listed_to->gte($article->published_at));
        $verification = $isJournalType ? Verifier::run($article, $article->journal) : null;

        $reviewHistory = DB::table('review_logs')
            ->leftJoin('users', 'users.id', '=', 'review_logs.user_id')
            ->where('review_logs.article_id', $article->id)
            ->orderByDesc('review_logs.created_at')
            ->orderByDesc('review_logs.id')
            ->get(['review_logs.*', 'users.name as reviewer_name']);

        return view('moderator.article', [
            'article' => $article,
            'articleType' => $articleType,
            'isJournalType' => $isJournalType,
            'journalCandidate' => $journalCandidate,
            'journalSuggestions' => $journalSuggestions,
            'journalListedAtPublication' => $journalListedAtPublication,
            'verification' => $verification,
            'reviewHistory' => $reviewHistory,
            'fields' => config('uniscience.fields'),
        ]);
    }

    public function certificateDecision(Request $request, Article $article)
    {
        $reviewer = $this->reviewer();
        $this->authorizeArticleReview($reviewer, $article);
        abort_unless((config('uniscience.article_types.'.$article->type.'.family') ?? null) === 'conference', 404);
        $certificate = $article->conferenceCertificate()->firstOrFail();
        abort_unless($certificate->status === 'pending', 422);

        $data = $request->validate([
            'decision' => ['required', 'in:verify,reject'],
            'note' => ['required_if:decision,reject', 'nullable', 'string', 'max:2000'],
        ], [
            'decision.required' => 'Sertifikat bo‘yicha qarorni tanlang.',
            'decision.in' => 'Sertifikat qarori noto‘g‘ri.',
            'note.required_if' => 'Rad etish sababini kiriting.',
        ]);

        $old = $certificate->only(['status', 'note', 'verified_by', 'verified_at']);
        $certificate->update([
            'status' => $data['decision'] === 'verify' ? 'verified' : 'rejected',
            'note' => $data['note'] ?? null,
            'verified_by' => $reviewer->id,
            'verified_at' => now(),
        ]);
        AuditLog::record('conference_certificate.reviewed', $certificate, $old, $certificate->only(['status', 'note', 'verified_by', 'verified_at']));

        return back()->with('ok', $data['decision'] === 'verify' ? 'Konferensiya sertifikati tasdiqlandi.' : 'Konferensiya sertifikati rad etildi.');
    }

    public function certificatePdf(Article $article)
    {
        $reviewer = $this->reviewer();
        $this->authorizeArticleReview($reviewer, $article);
        $certificate = $article->conferenceCertificate()->firstOrFail();
        abort_unless(Storage::disk('local')->exists($certificate->path), 404);

        return response()->file(Storage::disk('local')->path($certificate->path), ['Content-Type' => 'application/pdf']);
    }

    private function reviewer(): User
    {
        $reviewer = auth()->user();
        abort_unless($reviewer && ($reviewer->isAdmin() || $reviewer->isModerator()), 403);

        return $reviewer;
    }

    private function authorizeArticleReview(User $reviewer, Article $article): void
    {
        abort_unless($reviewer->canReviewFaculty($article->user->faculty), 403);
    }
}
