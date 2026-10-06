<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\ArticleStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArticleDecisionService
{
    public function decide(
        Article $article,
        User $reviewer,
        string $decision,
        ?string $note,
        mixed $journalPick = null,
        ?string $field = null,
        array $checklist = [],
    ): ?string {
        $approved = $decision === 'approve';
        $old = $article->only(['status', 'reason', 'journal_id']);
        $journal = $article->journal;
        $articleType = config('uniscience.article_types.'.$article->type, []);
        $requiresJournal = ($articleType['family'] ?? 'journal') === 'journal';

        if ($approved && ($articleType['family'] ?? null) === 'conference' && $article->conferenceCertificate?->status !== 'verified') {
            throw ValidationException::withMessages([
                'conference_certificate' => 'Konferensiya sertifikati tasdiqlanmaguncha maqolani tasdiqlab bo‘lmaydi.',
            ]);
        }

        if ($approved && ! $journal && $requiresJournal) {
            $journal = Journal::find((int) $journalPick);
            if (! $journal) {
                return 'journal_pick';
            }
        }

        if ($approved && ! $requiresJournal && ! filled($article->field) && ! filled($field)) {
            return 'field';
        }

        $article->update([
            'status' => $approved ? 'approved' : 'rejected',
            'reason' => $approved ? 'Tasdiqlandi (moderator)' : 'Rad etildi: '.$note,
            'journal_id' => $approved ? $journal?->id : $article->journal_id,
            'field' => $field ?? $article->field,
            'decided_at' => now(),
            'decided_by' => $reviewer->id,
        ]);

        if ($approved && $journal && $article->issn && ! $journal->issn && ! Journal::where('issn', $article->issn)->exists()) {
            $journal->update(['issn' => $article->issn]);
        }

        $checklistNotes = [];
        if (array_key_exists('pdf_title_matches', $checklist)) {
            $checklistNotes[] = 'PDF sarlavhasi: '.($checklist['pdf_title_matches'] ? 'mos' : 'tekshirilmadi');
        }
        if (array_key_exists('author_positions_checked', $checklist)) {
            $checklistNotes[] = 'Mualliflik pozitsiyasi: '.($checklist['author_positions_checked'] ? 'tekshirildi' : 'tekshirilmagan');
        }
        $reviewNote = trim(implode("\n", array_filter([$note, ...$checklistNotes])));

        AuditLog::record('article.review_decided', $article, $old, $article->only(['status', 'reason', 'journal_id', 'field']) + ['checklist' => $checklist]);
        DB::table('review_logs')->insert([
            'article_id' => $article->id,
            'user_id' => $reviewer->id,
            'decision' => $approved ? 'approved' : 'rejected',
            'note' => $reviewNote !== '' ? $reviewNote : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $article->user->notify(new ArticleStatus($article->fresh()));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return null;
    }
}
