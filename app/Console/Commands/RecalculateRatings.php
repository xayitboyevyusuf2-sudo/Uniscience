<?php

namespace App\Console\Commands;

use App\Services\RatingService;
use Illuminate\Console\Command;

class RecalculateRatings extends Command
{
    protected $signature = 'ratings:recalculate';

    protected $description = 'Barcha foydalanuvchilar reyting jadvallarini qayta hisoblash (oylik, W_sana vaqtga bog‘liq)';

    public function handle(): int
    {
        $count = RatingService::recalculateAll();
        $this->info($count.' ta foydalanuvchi reytingi qayta hisoblandi.');

        return self::SUCCESS;
    }
}
