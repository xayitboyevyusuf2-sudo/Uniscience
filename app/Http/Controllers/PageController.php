<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Certificate;
use App\Models\Journal;
use App\Models\User;
use App\Services\Scorer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function home()
    {
        return view('home', ['score' => auth()->check() ? Scorer::for(auth()->user()) : null,
            'stats' => ['students' => User::where('role', 'student')->count(), 'articles' => Article::where('status', 'approved')->count(), 'journals' => Journal::count()]]);
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
            $q->whereHas('journal', fn ($x) => $x->where('field', 'like', '%'.$r->field.'%'));
        }
        if ($r->tier) {
            $q->whereHas('journal', fn ($x) => $x->where('tier', $r->tier));
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

    public function verify($token) // public, read-only (FR-28)
    {$c = Certificate::where('token', $token)->with('user')->firstOrFail();

        return view('verify', ['c' => $c, 's' => Scorer::for($c->user)]);
    }
}
