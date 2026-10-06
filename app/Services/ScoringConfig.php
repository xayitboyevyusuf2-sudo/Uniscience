<?php

namespace App\Services;

use App\Models\Setting;

/**
 * FR-53: scoring coefficients. config('uniscience') holds the defaults; rows in the
 * settings table override them. When no override exists, every value matches the old
 * behavior exactly. Keys: tier.A…tier.X, position.yolgiz|birinchi|oxirgi|ortadagi,
 * date.y1|y2|y3|older, field.same|related|other, yearly_limit, diversity_factor,
 * monthly_flag_threshold.
 */
class ScoringConfig
{
    public static function tiers(): array
    {
        $defaults = config('uniscience.tiers');
        foreach (array_keys($defaults) as $tier) {
            $defaults[$tier] = self::num('tier.'.$tier, $defaults[$tier]);
        }

        return $defaults;
    }

    public static function positions(): array
    {
        $defaults = config('uniscience.positions');
        foreach (array_keys($defaults) as $position) {
            $defaults[$position] = self::num('position.'.$position, $defaults[$position]);
        }

        return $defaults;
    }

    public static function dateWeight(string $key, float $default): float
    {
        return self::num('date.'.$key, $default);
    }

    public static function fieldWeight(string $key, float $default): float
    {
        return self::num('field.'.$key, $default);
    }

    public static function yearlyLimit(): int
    {
        return (int) self::num('yearly_limit', config('uniscience.yearly_limit'));
    }

    public static function diversityFactor(): float
    {
        return self::num('diversity_factor', 0.8);
    }

    public static function monthlyFlagThreshold(): int
    {
        return (int) self::num('monthly_flag_threshold', 5);
    }

    public static function all(): array
    {
        return [
            'tier' => self::tiers(),
            'position' => self::positions(),
            'date' => ['y1' => self::dateWeight('y1', 1.0), 'y2' => self::dateWeight('y2', 0.8), 'y3' => self::dateWeight('y3', 0.6), 'older' => self::dateWeight('older', 0.4)],
            'field' => ['same' => self::fieldWeight('same', 1.0), 'related' => self::fieldWeight('related', 0.7), 'other' => self::fieldWeight('other', 0.5)],
            'yearly_limit' => self::yearlyLimit(),
            'diversity_factor' => self::diversityFactor(),
            'monthly_flag_threshold' => self::monthlyFlagThreshold(),
        ];
    }

    private static function num(string $key, float|int $default): float|int
    {
        $value = Setting::get($key);

        return $value === null ? $default : (str_contains((string) $value, '.') ? (float) $value : (int) $value);
    }
}
