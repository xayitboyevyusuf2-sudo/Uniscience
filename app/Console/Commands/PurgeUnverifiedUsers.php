<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:purge-unverified')]
#[Description('Delete student accounts that have not verified their email within 72 hours')]
class PurgeUnverifiedUsers extends Command
{
    public function handle(): int
    {
        $deleted = User::query()
            ->where('role', 'student')
            ->whereNull('email_verified_at')
            ->where('created_at', '<', now()->subHours(72))
            ->delete();

        $this->info("{$deleted} ta tasdiqlanmagan talaba hisobi o‘chirildi.");

        return self::SUCCESS;
    }
}
