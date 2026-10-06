<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Local-only demo dataset for measuring list-page performance. Run explicitly: php artisan db:seed --class=DemoSeeder */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoSeeder faqat mahalliy muhitda ishga tushiriladi.');

            return;
        }

        $journals = Journal::query()->limit(10)->get();
        if ($journals->isEmpty()) {
            $journals = collect(range(1, 10))->map(fn ($i) => Journal::create([
                'name' => 'Demo jurnal '.$i,
                'field' => 'Iqtisodiyot',
                'tier' => ['A', 'B', 'C', 'D', 'E'][$i % 5],
                'listed_from' => '2019-01-01',
            ]));
        }

        User::factory()->count(1000)->create();
        $users = User::query()->where('role', 'student')->orderBy('id')->limit(1000)->get();
        foreach ($users as $user) {
            if (fake()->boolean(50) && Article::count() < 500) {
                $journal = $journals->random();
                Article::create([
                    'user_id' => $user->id,
                    'title' => 'Demo maqola '.uniqid(),
                    'type' => 'journal_local_oak',
                    'journal_id' => $journal->id,
                    'journal_name' => $journal->name,
                    'published_at' => now()->subDays(fake()->numberBetween(1, 700))->toDateString(),
                    'url' => 'https://example.uz/'.uniqid(),
                    'position' => 'yolgiz',
                    'status' => 'approved',
                    'decided_at' => now()->subDays(fake()->numberBetween(1, 300)),
                ]);
            }
        }
        $this->command?->info('DemoSeeder: '.User::count().' foydalanuvchi, '.Article::count().' maqola.');
    }
}
