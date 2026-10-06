<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;

/** Score = sum(W_soha x W_daraja x W_muallif x W_sana), FR-15..20. */
class Scorer
{
    public static function for(User $u): array
    {
        $tiers = ScoringConfig::tiers();
        $positions = ScoringConfig::positions();
        $limit = ScoringConfig::yearlyLimit();
        $rows = [];
        $cnt = [];
        $arts = $u->articles()->where('status', 'approved')->with('journal')->orderBy('created_at')->get();
        foreach ($arts as $a) {
            $tier = $a->effectiveTier();
            if (! $tier || ! array_key_exists($tier, $tiers)) {
                continue;
            }
            $y = $a->published_at->year;
            $cnt[$y] = ($cnt[$y] ?? 0) + 1;
            $f = [self::wField($u->direction, $a->effectiveField()), $tiers[$tier], $positions[$a->position], self::wDate($a->published_at)];
            $ok = $cnt[$y] <= $limit;
            $rows[$a->id] = ['article' => $a, 'f' => $f, 'counted' => $ok, 'pts' => $ok ? array_product($f) : 0];
        }
        $counted = array_filter($rows, fn ($r) => $r['counted']);
        $div = count($counted) >= 2 && collect($counted)->map(fn ($r) => $r['article']->venueKey())->uniqueStrict()->count() === 1;
        $total = array_sum(array_column($rows, 'pts')) * ($div ? ScoringConfig::diversityFactor() : 1);

        return ['rows' => $rows, 'total' => round($total, 2), 'diversity' => $div];
    }

    /**
     * FR-33 (T8): professor/tadqiqotchi uchun o‘z tasdiqlangan maqolalari plus articles where
     * they are a linked co-author in 'oxirgi' (supervisor) position (W_muallif = 0.6).
     * Scorer::for() itself is UNCHANGED. ASSUMPTION: the yearly counted limit applies only
     * to the mentor's own articles — supervised articles are never subject to it.
     *
     * @return array{rows: array<int, array{article: Article, f: array, counted: bool, pts: float, supervised: bool}>, total: float}
     */
    public static function forSupervised(User $u): array
    {
        $tiers = ScoringConfig::tiers();
        $own = self::for($u);
        $supervised = Article::query()->where('status', 'approved')->with('journal')
            ->whereHas('authors', fn ($q) => $q->where('user_id', $u->id)->where('position', 'oxirgi'))
            ->where('user_id', '!=', $u->id)
            ->orderBy('created_at')->get();

        $rows = [];
        foreach ($own['rows'] as $id => $row) {
            $rows[$id] = $row + ['supervised' => false];
        }
        foreach ($supervised as $article) {
            if (isset($rows[$article->id])) {
                continue;
            }
            $tier = $article->effectiveTier();
            if (! $tier || ! array_key_exists($tier, $tiers)) {
                continue;
            }
            $f = [self::wField($u->direction, $article->effectiveField()), $tiers[$tier], ScoringConfig::positions()['oxirgi'], self::wDate($article->published_at)];
            $rows[$article->id] = ['article' => $article, 'f' => $f, 'counted' => true, 'pts' => array_product($f), 'supervised' => true];
        }

        return ['rows' => $rows, 'total' => round(array_sum(array_column($rows, 'pts')), 2)];
    }

    public static function wField($mine, $jf)
    {
        $fs = array_map('trim', explode(';', (string) $jf));
        if (in_array($mine, $fs, true)) {
            return ScoringConfig::fieldWeight('same', 1.0);
        }
        foreach (config('uniscience.related')[$mine] ?? [] as $r) {
            if (in_array($r, $fs, true)) {
                return ScoringConfig::fieldWeight('related', 0.7);
            }
        }

        return ScoringConfig::fieldWeight('other', 0.5);
    }

    public static function wDate($d)
    {
        $y = abs(now()->diffInDays($d)) / 365.25;

        return $y <= 1 ? ScoringConfig::dateWeight('y1', 1.0) : ($y <= 2 ? ScoringConfig::dateWeight('y2', 0.8) : ($y <= 3 ? ScoringConfig::dateWeight('y3', 0.6) : ScoringConfig::dateWeight('older', 0.4)));
    }
}
