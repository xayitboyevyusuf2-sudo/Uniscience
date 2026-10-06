<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

/** Score = sum(W_soha x W_daraja x W_muallif x W_sana), FR-15..20. */
class Scorer
{
    public static function for(User $u): array
    {
        $c = config('uniscience');
        $limit = (int) Setting::get('yearly_limit', $c['yearly_limit']);
        $rows = [];
        $cnt = [];
        $arts = $u->articles()->where('status', 'approved')->with('journal')->orderBy('created_at')->get();
        foreach ($arts as $a) {
            $tier = $a->effectiveTier();
            if (! $tier || ! array_key_exists($tier, $c['tiers'])) {
                continue;
            }
            $y = $a->published_at->year;
            $cnt[$y] = ($cnt[$y] ?? 0) + 1;
            $f = [self::wField($u->direction, $a->effectiveField()), $c['tiers'][$tier], $c['positions'][$a->position], self::wDate($a->published_at)];
            $ok = $cnt[$y] <= $limit;
            $rows[$a->id] = ['article' => $a, 'f' => $f, 'counted' => $ok, 'pts' => $ok ? array_product($f) : 0];
        }
        $counted = array_filter($rows, fn ($r) => $r['counted']);
        $div = count($counted) >= 2 && collect($counted)->map(fn ($r) => $r['article']->venueKey())->uniqueStrict()->count() === 1;
        $total = array_sum(array_column($rows, 'pts')) * ($div ? 0.8 : 1);

        return ['rows' => $rows, 'total' => round($total, 2), 'diversity' => $div];
    }

    public static function wField($mine, $jf)
    {
        $fs = array_map('trim', explode(';', (string) $jf));
        if (in_array($mine, $fs, true)) {
            return 1.0;
        }
        foreach (config('uniscience.related')[$mine] ?? [] as $r) {
            if (in_array($r, $fs, true)) {
                return 0.7;
            }
        }

        return 0.5;
    }

    public static function wDate($d)
    {
        $y = abs(now()->diffInDays($d)) / 365.25;

        return $y <= 1 ? 1.0 : ($y <= 2 ? 0.8 : ($y <= 3 ? 0.6 : 0.4));
    }
}
