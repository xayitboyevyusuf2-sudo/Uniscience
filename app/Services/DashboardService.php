<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Leadership dashboard aggregates. All numbers come from indexed aggregate queries —
 * no N+1, no per-row PHP loops over articles.
 */
class DashboardService
{
    /** @return array<string, mixed> */
    public static function build(string $period, ?int $month, ?int $year, ?string $faculty): array
    {
        $year = $year ?? (int) now()->format('Y');
        [$from, $to] = self::bounds($period, $month, $year);

        $articles = DB::table('articles')->join('users', 'users.id', '=', 'articles.user_id');
        if ($faculty) {
            $articles->where('users.faculty', $faculty);
        }

        $byFaculty = (clone $articles)
            ->whereBetween('articles.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('users.faculty')
            ->selectRaw('users.faculty as faculty, COUNT(*) as uploaded')
            ->pluck('uploaded', 'faculty');

        $approvedByFaculty = (clone $articles)
            ->where('articles.status', 'approved')
            ->whereBetween('articles.decided_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('users.faculty')
            ->selectRaw('users.faculty as faculty, COUNT(*) as approved')
            ->pluck('approved', 'faculty');

        $facultyRows = $byFaculty->keys()->merge($approvedByFaculty->keys())->unique()->sort()->map(
            fn ($f) => ['faculty' => $f, 'uploaded' => (int) ($byFaculty[$f] ?? 0), 'approved' => (int) ($approvedByFaculty[$f] ?? 0)]
        )->values();

        $monthExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', articles.decided_at) AS INTEGER)"
            : 'MONTH(articles.decided_at)';
        $monthly = (clone $articles)
            ->where('articles.status', 'approved')
            ->whereYear('articles.decided_at', $year)
            ->groupByRaw($monthExpr)
            ->selectRaw($monthExpr.' as month, COUNT(*) as approved')
            ->pluck('approved', 'month');
        $dynamics = collect(range(1, 12))->map(fn ($m) => ['month' => $m, 'approved' => (int) ($monthly[$m] ?? 0)]);

        $tiers = (clone $articles)->where('articles.status', 'approved')->get(['articles.id', 'articles.type', 'articles.journal_id', 'articles.field']);
        $tierDistribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'X' => 0];
        $journalsById = DB::table('journals')->whereIn('id', $tiers->pluck('journal_id')->filter()->unique())->pluck('tier', 'id');
        $forcedTiers = collect(config('uniscience.article_types'))->mapWithKeys(fn ($type, $key) => [$key => $type['forced_tier'] ?? null]);
        foreach ($tiers as $article) {
            $tier = $forcedTiers[$article->type] ?? $journalsById[$article->journal_id] ?? null;
            if ($tier && array_key_exists($tier, $tierDistribution)) {
                $tierDistribution[$tier]++;
            }
        }

        $top = fn (string $category) => DB::table('ratings')->join('users', 'users.id', '=', 'ratings.user_id')
            ->where('ratings.category', $category)
            ->when($faculty, fn ($q) => $q->where('users.faculty', $faculty))
            ->orderByDesc('ratings.score')->limit(10)
            ->get(['users.name', 'users.faculty', 'ratings.score', 'ratings.rank_faculty', 'ratings.rank_university']);

        $byCategory = DB::table('articles')->join('users', 'users.id', '=', 'articles.user_id')
            ->where('articles.status', 'approved')
            ->whereBetween('articles.decided_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($faculty, fn ($q) => $q->where('users.faculty', $faculty))
            ->groupBy('users.category')
            ->selectRaw('users.category as category, COUNT(*) as count')
            ->pluck('count', 'category');

        $byDepartment = DB::table('articles')->join('users', 'users.id', '=', 'articles.user_id')
            ->where('articles.status', 'approved')
            ->whereBetween('articles.decided_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($faculty, fn ($q) => $q->where('users.faculty', $faculty))
            ->whereNotNull('users.department')
            ->groupBy('users.department')
            ->selectRaw('users.department as department, COUNT(*) as count')
            ->pluck('count', 'department');

        $byCourse = DB::table('articles')->join('users', 'users.id', '=', 'articles.user_id')
            ->where('articles.status', 'approved')
            ->whereBetween('articles.decided_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($faculty, fn ($q) => $q->where('users.faculty', $faculty))
            ->whereNotNull('users.course')
            ->groupBy('users.course')
            ->selectRaw('users.course as course, COUNT(*) as count')
            ->pluck('count', 'course');

        return [
            'period' => $period, 'month' => $month, 'year' => $year, 'faculty' => $faculty,
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'facultyRows' => $facultyRows,
            'dynamics' => $dynamics,
            'tierDistribution' => $tierDistribution,
            'topStudents' => $top('bakalavr'),
            'topMasters' => $top('magistr'),
            'topProfessors' => $top('professor'),
            'faculties' => DB::table('users')->whereNotNull('faculty')->where('faculty', '!=', '')->distinct()->orderBy('faculty')->pluck('faculty'),
            'byCategory' => $byCategory,
            'byDepartment' => $byDepartment,
            'byCourse' => $byCourse,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private static function bounds(string $period, ?int $month, int $year): array
    {
        return match ($period) {
            'oy' => [now()->setYear($year)->setMonth($month ?? (int) now()->format('n'))->startOfMonth()->startOfDay(), now()->setYear($year)->setMonth($month ?? (int) now()->format('n'))->endOfMonth()->endOfDay()],
            'chorak' => [now()->setYear($year)->startOfYear(), now()->setYear($year)->endOfYear()],
            default => [now()->setYear($year)->startOfYear(), now()->setYear($year)->endOfYear()],
        };
    }
}
