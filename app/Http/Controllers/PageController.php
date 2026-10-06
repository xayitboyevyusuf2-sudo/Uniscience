<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Certificate;
use App\Models\Journal;
use App\Models\News;
use App\Models\User;
use App\Services\Scorer;
use App\Services\Verifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function home()
    {
        return view('home', ['score' => auth()->check() ? Scorer::for(auth()->user()) : null,
            'stats' => ['students' => User::where('role', 'student')->count(), 'articles' => Article::where('status', 'approved')->count(), 'journals' => Journal::count()],
            'latestNews' => auth()->check() ? News::active()->orderByDesc('pinned')->latest()->take(3)->get() : collect()]);
    }

    public function rating(Request $r)
    {
        $me = auth()->user();
        $rows = User::where('role', 'student')->get()->map(fn ($u) => ['u' => $u, 's' => Scorer::for($u)['total']])
            ->filter(fn ($x) => $x['s'] > 0 || $x['u']->id === $me?->id);
        if ($r->scope === 'faculty' && $me) {
            $rows = $rows->filter(fn ($x) => $x['u']->faculty === $me->faculty);
        }

        return view('rating', ['rows' => $rows->sortByDesc('s')->values()]);
    }

    public function base(Request $r)
    {
        $q = Article::with(['journal', 'user'])->where('status', 'approved');
        if ($t = $r->q) {
            $q->where(fn ($x) => $x->where('title', 'like', "%$t%")->orWhere('abstract', 'like', "%$t%")
                ->orWhere('annotation_uz', 'like', "%$t%")->orWhere('annotation_ru', 'like', "%$t%")->orWhere('annotation_en', 'like', "%$t%")
                ->orWhere('keywords_uz', 'like', "%$t%")->orWhere('keywords_ru', 'like', "%$t%")->orWhere('keywords_en', 'like', "%$t%")
                ->orWhere('coauthors', 'like', "%$t%")->orWhereHas('user', fn ($y) => $y->where('name', 'like', "%$t%")));
        }
        if ($r->field) {
            $q->where(fn ($x) => $x->where('articles.field', 'like', '%'.$r->field.'%')->orWhereHas('journal', fn ($j) => $j->where('field', 'like', '%'.$r->field.'%')));
        }
        if ($r->tier) {
            $forcedTypes = array_keys(array_filter(config('uniscience.article_types'), fn ($type) => ($type['forced_tier'] ?? null) === $r->tier));
            $q->where(fn ($x) => $x->whereIn('articles.type', $forcedTypes)->orWhereHas('journal', fn ($j) => $j->where('tier', $r->tier)));
        }
        if ($r->year) {
            $q->whereYear('published_at', (int) $r->year);
        }

        return view('base', ['items' => $q->latest('published_at')->paginate(10)->withQueryString()]);
    }

    public function certify()
    {
        $c = Certificate::create(['user_id' => auth()->id(), 'token' => Str::random(24)]);

        return redirect("/malumotnoma/{$c->token}");
    }

    public function journals(Request $r) // read-only reference for any authenticated user (FR-29)
    {
        $filters = $r->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'field' => ['nullable', 'string', 'max:190'],
            'tier' => ['nullable', 'string', 'max:1'],
        ]);
        $q = Journal::query()->orderBy('name');
        if ($t = trim((string) ($filters['q'] ?? ''))) {
            $n = Verifier::norm($t);
            $q->where(fn ($x) => $x->where('name_norm', 'like', '%'.$n.'%')->orWhere('issn', 'like', '%'.$t.'%'));
        }
        if ($f = $filters['field'] ?? null) {
            $q->where('field', 'like', '%'.$f.'%');
        }
        if ($tier = $filters['tier'] ?? null) {
            $q->where('tier', $tier);
        }

        return view('journals.index', ['journals' => $q->paginate(25)->withQueryString()]);
    }

    public function verify($token) // public, read-only (FR-28)
    {
        $c = Certificate::where('token', $token)->with('user')->firstOrFail();

        return view('verify', ['c' => $c, 's' => Scorer::for($c->user)]);
    }
}
