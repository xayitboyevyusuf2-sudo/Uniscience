<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cached rating tables. Scorer is the SINGLE source of scoring truth — these tables
 * only cache Scorer::for() results and ranks computed from them.
 * Ranks are assigned within category; equal scores share a rank (1, 2, 2, 4).
 */
class RatingService
{
    public const CATEGORIES = ['bakalavr', 'magistr', 'tadqiqotchi', 'professor'];

    public static function recalculateUser(User $user): Rating
    {
        $score = Scorer::for($user);

        return DB::transaction(function () use ($user, $score): Rating {
            $rating = Rating::firstOrNew(['user_id' => $user->id]);
            $rating->forceFill([
                'category' => $user->category ?? 'bakalavr',
                'score' => $score['total'],
                'computed_at' => now(),
            ])->save();

            $rating->items()->delete();
            foreach ($score['rows'] as $row) {
                $rating->items()->create([
                    'article_id' => $row['article']->id,
                    'w_soha' => $row['f'][0],
                    'w_daraja' => $row['f'][1],
                    'w_muallif' => $row['f'][2],
                    'w_sana' => $row['f'][3],
                    'points' => $row['pts'],
                    'counted' => $row['counted'],
                ]);
            }

            return $rating->refresh();
        });
    }

    public static function recalculateAll(): int
    {
        User::query()->select('id')->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $stub) {
                self::recalculateUser(User::find($stub->id));
            }
        });
        self::refreshRanks();

        return Rating::count();
    }

    /** Fast path for single-user changes (article decision, profile/category change). */
    public static function refreshAfterChange(User $user): void
    {
        $rating = self::recalculateUser($user);
        self::refreshRanksFor($rating->category);
    }

    /** Assign competition ranks (1, 2, 2, 4) within each category for every scope. */
    public static function refreshRanks(): void
    {
        foreach (self::CATEGORIES as $category) {
            self::refreshRanksFor($category);
        }
    }

    public static function refreshRanksFor(string $category): void
    {
        $ratings = Rating::where('category', $category)->with('user')->get();
        self::assignRanks($ratings, 'rank_university', fn (Rating $r): string => $r->user?->university ?? '');
        self::assignRanks($ratings, 'rank_faculty', fn (Rating $r): string => ($r->user?->university ?? '').'|'.($r->user?->faculty ?? ''));
        self::assignRanks($ratings, 'rank_group', fn (Rating $r): string => ($r->user?->university ?? '').'|'.($r->user?->faculty ?? '').'|'.($r->user?->group_name ?? ''));
    }

    /** @param  Collection<int, Rating>  $ratings */
    private static function assignRanks(Collection $ratings, string $column, callable $groupKey): void
    {
        $groups = $ratings->groupBy($groupKey);
        foreach ($groups as $members) {
            $rank = 0;
            $position = 0;
            $previous = null;
            foreach ($members->sortBy([['score', 'desc'], ['user_id', 'asc']])->values() as $member) {
                $position++;
                if ($previous === null || bccomp((string) $member->score, (string) $previous, 2) !== 0) {
                    $rank = $position;
                    $previous = (string) $member->score;
                }
                $member->forceFill([$column => $rank])->saveQuietly();
            }
        }
    }
}
