<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeUnverifiedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_removes_only_unverified_students_older_than_seventy_two_hours(): void
    {
        $this->travelTo(now()->startOfDay());
        $expiredStudent = User::factory()->unverified()->create([
            'created_at' => now()->subHours(72)->subSecond(),
        ]);
        $boundaryStudent = User::factory()->unverified()->create([
            'created_at' => now()->subHours(72),
        ]);
        $verifiedStudent = User::factory()->create([
            'created_at' => now()->subHours(100),
        ]);
        $protectedRoles = collect(['admin', 'moderator', 'rahbariyat'])
            ->map(fn (string $role) => User::factory()->unverified()->create([
                'role' => $role,
                'created_at' => now()->subHours(100),
            ]));

        $this->artisan('users:purge-unverified')
            ->expectsOutputToContain('1 ta tasdiqlanmagan talaba hisobi o‘chirildi.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $expiredStudent->id]);
        $this->assertDatabaseHas('users', ['id' => $boundaryStudent->id]);
        $this->assertDatabaseHas('users', ['id' => $verifiedStudent->id]);

        foreach ($protectedRoles as $user) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }
}
