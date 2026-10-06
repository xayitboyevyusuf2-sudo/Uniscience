<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Services\RatingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatingController extends Controller
{
    public const TOP_LIMIT = 50;

    public const NEIGHBORHOOD = 5;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'in:'.implode(',', RatingService::CATEGORIES)],
            'scope' => ['nullable', 'string', 'in:university,faculty,group'],
        ]);
        $category = $filters['category'] ?? 'bakalavr';
        $scope = $filters['scope'] ?? 'university';
        $me = auth()->user();
        $rankColumn = 'rank_'.$scope;

        $base = Rating::where('category', $category)->with('user');
        if ($me) {
            if ($scope === 'faculty') {
                $base->whereHas('user', fn ($q) => $q->where('university', $me->university)->where('faculty', $me->faculty));
            }
            if ($scope === 'group') {
                $base->whereHas('user', fn ($q) => $q->where('university', $me->university)->where('faculty', $me->faculty)->where('group_name', $me->group_name));
            }
        }

        $top = (clone $base)->where('score', '>', 0)->whereNotNull($rankColumn)
            ->orderBy($rankColumn)->orderBy('user_id')->limit(self::TOP_LIMIT)->get();

        $neighborhood = collect();
        $myRating = null;
        if ($me) {
            $myRating = Rating::where('user_id', $me->id)->first() ?? RatingService::recalculateUser($me);
            if ($myRating->category !== $category) {
                $myRating = null;
            }
        }
        if ($myRating && $myRating->{$rankColumn} !== null && ! $top->contains('user_id', $me->id)) {
            $all = (clone $base)->whereNotNull($rankColumn)->orderBy($rankColumn)->orderBy('user_id')->get();
            $pos = $all->search(fn ($r) => $r->user_id === $me->id);
            if ($pos !== false) {
                $neighborhood = $all->slice(max(0, $pos - self::NEIGHBORHOOD), self::NEIGHBORHOOD * 2 + 1)->values();
            }
        }

        return view('rating', [
            'top' => $top,
            'neighborhood' => $neighborhood,
            'category' => $category,
            'scope' => $scope,
            'rankColumn' => $rankColumn,
            'categories' => RatingService::CATEGORIES,
            'canExport' => $me?->isAdmin() || $me?->isLeadership(),
        ]);
    }

    public function my(): View
    {
        $user = auth()->user();
        $rating = Rating::where('user_id', $user->id)->with('items.article')->first() ?? RatingService::recalculateUser($user);
        $rating->loadMissing('items.article');

        return view('rating.my', ['rating' => $rating, 'user' => $user]);
    }

    public function exportCsv(): StreamedResponse
    {
        $this->guardExport();

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads o‘/g‘ correctly
            fputcsv($out, ['F.I.Sh.', 'Toifa', 'Universitet', 'Fakultet', 'Guruh', 'Ball', 'Guruh o‘rni', 'Fakultet o‘rni', 'Universitet o‘rni']);
            Rating::with('user')->orderByDesc('score')->chunk(500, function ($ratings) use ($out): void {
                foreach ($ratings as $rating) {
                    fputcsv($out, [
                        $rating->user?->fullName() ?: $rating->user?->name,
                        $rating->category,
                        $rating->user?->university,
                        $rating->user?->faculty,
                        $rating->user?->group_name,
                        $rating->score,
                        $rating->rank_group,
                        $rating->rank_faculty,
                        $rating->rank_university,
                    ]);
                }
            });
            fclose($out);
        }, 'reyting.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(): Response
    {
        $this->guardExport();
        $ratings = Rating::with('user')->where('score', '>', 0)->orderByDesc('score')->limit(self::TOP_LIMIT)->get();

        return Pdf::setOptions(['defaultFont' => 'DejaVu Sans'])
            ->loadView('rating.export-pdf', ['ratings' => $ratings])
            ->download('reyting.pdf');
    }

    private function guardExport(): void
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isLeadership(), 403);
    }
}
