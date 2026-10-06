<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class ArchiveExpiredNews extends Command
{
    protected $signature = 'news:archive';

    protected $description = 'Muddati o‘tgan yangiliklarga archived_at qo‘yish (kunlik)';

    public function handle(): int
    {
        $count = News::query()->whereNull('archived_at')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', now()->toDateString())
            ->update(['archived_at' => now()]);
        $this->info($count.' ta yangilik arxivlandi.');

        return self::SUCCESS;
    }
}
