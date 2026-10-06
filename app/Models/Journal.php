<?php

namespace App\Models;

use App\Services\Verifier;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    public const SOURCES = ['mahalliy OAK', 'xalqaro OAK', 'Scopus/ESCI/WoS'];

    public const TIER_X_WARNING = 'Ushbu jurnal xavfli jurnallar ro‘yxatida. Maqola 0 ball oladi.';

    protected $guarded = [];

    protected $casts = ['listed_from' => 'date', 'listed_to' => 'date'];

    protected static function booted(): void
    {
        static::saving(fn ($j) => $j->name_norm = Verifier::norm($j->name));
    }

    public static function sourceForTier(string $tier): ?string
    {
        return match ($tier) {
            'D' => 'mahalliy OAK',
            'C' => 'xalqaro OAK',
            default => null,
        };
    }

    public function warningMessage(): ?string
    {
        if ($this->tier === 'X') {
            return $this->warning_text ?: self::TIER_X_WARNING;
        }

        return $this->warning_text;
    }

    public function isListed(): bool
    {
        return $this->listed_to === null;
    }
}
