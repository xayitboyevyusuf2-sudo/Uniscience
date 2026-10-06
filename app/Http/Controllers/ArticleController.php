<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Models\Article;
use App\Models\Journal;
use App\Models\JournalRequest;
use App\Models\User;
use App\Notifications\ArticleStatus;
use App\Notifications\ArticleSubmitted;
use App\Services\Scorer;
use App\Services\Verifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function portfolio(): View
    {
        $user = auth()->user();

        return view('articles.portfolio', ['s' => Scorer::for($user), 'articles' => $user->articles()->latest()->get()]);
    }

    public function create(): View
    {
        return view('articles.create', [
            'mentors' => User::query()
                ->whereIn('category', ['professor', 'tadqiqotchi'])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'patronymic']),
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = config('uniscience.article_types.'.$data['type']);
        $family = $type['family'];
        $authors = $data['authors'];
        $submitterIndex = collect($authors)->search(fn (array $author): bool => filter_var($author['is_submitter'] ?? false, FILTER_VALIDATE_BOOLEAN));
        $submitter = $authors[$submitterIndex];
        $otherAuthorNames = collect($authors)->reject(fn (array $author, int $index): bool => $index === $submitterIndex)
            ->pluck('full_name')->implode(', ');
        $pdfPath = $request->file('pdf')->store('articles', 'local');
        $certificatePath = $family === 'conference'
            ? $request->file('conference_certificate')->store('conference-certificates', 'local')
            : null;
        $hint = ! empty($data['journal_id']) ? Journal::find($data['journal_id']) : null;

        $article = DB::transaction(function () use ($data, $authors, $submitter, $submitterIndex, $otherAuthorNames, $pdfPath, $certificatePath): Article {
            $article = Article::create([
                'user_id' => auth()->id(),
                'title' => $data['title'],
                'type' => $data['type'],
                'journal_name' => $data['journal_name'],
                'issn' => $data['issn'] ?? null,
                'field' => $data['field'] ?? null,
                'doi' => filled($data['doi'] ?? null) ? Str::lower(trim($data['doi'])) : null,
                'published_at' => $data['published_at'],
                'url' => $data['url'],
                'pdf_path' => $pdfPath,
                'annotation_uz' => $data['annotation_uz'],
                'annotation_ru' => $data['annotation_ru'],
                'annotation_en' => $data['annotation_en'],
                'abstract' => $data['annotation_uz'],
                'keywords_uz' => $data['keywords_uz'],
                'keywords_ru' => $data['keywords_ru'],
                'keywords_en' => $data['keywords_en'],
                'coauthors' => Str::limit($otherAuthorNames, 500, ''),
                'position' => $submitter['position'],
                'status' => 'pending',
            ]);

            foreach ($authors as $sort => $author) {
                $isSubmitter = $sort === $submitterIndex;
                $linkedUser = $isSubmitter ? auth()->user() : (! empty($author['user_id']) ? User::find($author['user_id']) : null);
                $article->authors()->create([
                    'user_id' => $linkedUser?->id,
                    'full_name' => $linkedUser?->fullName() ?: trim($author['full_name']),
                    'position' => $author['position'],
                    'is_submitter' => $isSubmitter,
                    'sort' => $sort,
                ]);
            }

            if ($certificatePath !== null) {
                $article->conferenceCertificate()->create(['path' => $certificatePath]);
            }

            return $article;
        });

        auth()->user()->notify(new ArticleSubmitted($article));

        $journalRequestRequired = false;
        $journalRequestNote = null;
        if ($family === 'journal') {
            if (filter_var($data['journal_not_listed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $status = 'manual';
                $reason = 'Foydalanuvchi jurnal OAK ro‘yxatida yo‘qligini bildirdi. Moderator tekshiradi.';
                $journal = null;
                $journalRequestRequired = true;
                $journalRequestNote = 'Foydalanuvchi jurnal ro‘yxatda yo‘qligini belgiladi.';
            } else {
                [$status, $reason, $journal] = Verifier::run($article, $hint);
                if ($status === 'manual' && str_contains($reason, 'Jurnal OAK ro‘yxatidan topilmadi')) {
                    $journalRequestRequired = true;
                    $journalRequestNote = $reason;
                }
            }
        } elseif ($family === 'scopus') {
            $status = 'manual';
            $reason = 'Scopus/WoS nashri OAK avtomatik tekshiruvidan o‘tkazilmaydi. Moderator tekshiradi.';
            $journal = null;
        } else {
            $status = 'manual';
            $reason = 'Konferensiya sertifikati moderator tomonidan tekshiriladi.';
            $journal = null;
        }

        if ($journalRequestRequired) {
            JournalRequest::create([
                'article_id' => $article->id,
                'user_id' => auth()->id(),
                'journal_name' => $article->journal_name,
                'issn' => $article->issn,
                'status' => 'open',
                'note' => $journalRequestNote,
            ]);
        }

        $decision = ['status' => $status, 'reason' => $reason, 'journal_id' => $journal?->id];
        if (in_array($status, ['approved', 'rejected'], true)) {
            $decision['decided_at'] = now();
        }
        $article->update($decision);
        DB::table('review_logs')->insert([
            'article_id' => $article->id,
            'decision' => $status,
            'note' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (in_array($status, ['approved', 'rejected'], true)) {
            try {
                auth()->user()->notify(new ArticleStatus($article->fresh()));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect('/maqola/'.$article->id)->with('ok', $reason);
    }

    public function show(Article $article): View
    {
        $user = auth()->user();
        abort_unless($article->user_id === $user->id || $user->role === 'admin' || ($user->role === 'moderator' && $article->user->faculty === $user->faculty), 403);
        $article->load('authors');
        $row = Scorer::for($article->user)['rows'][$article->id] ?? null;

        return view('articles.show', compact('article', 'row'));
    }
}
