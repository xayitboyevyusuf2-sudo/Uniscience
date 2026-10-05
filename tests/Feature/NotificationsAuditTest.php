<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountApproved;
use App\Services\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationsAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_notification_owner_can_mark_it_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner->notify(new AccountApproved);
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($otherUser)
            ->post("/bildirishnomalar/{$notification->id}/oqildi")
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'read_at' => null]);

        $this->actingAs($owner)
            ->post("/bildirishnomalar/{$notification->id}/oqildi")
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_role_change_records_old_and_new_values_in_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'student', 'blocked' => false]);

        $this->actingAs($admin)
            ->post("/admin/foydalanuvchi/{$user->id}", ['role' => 'moderator', 'blocked' => 1])
            ->assertSessionHas('ok', 'Yangilandi');

        $entry = DB::table('audit_logs')->where('action', 'user.role_or_block_updated')->first();

        $this->assertNotNull($entry);
        $this->assertSame(['role' => 'student', 'blocked' => false], json_decode($entry->old, true));
        $this->assertSame(['role' => 'moderator', 'blocked' => true], json_decode($entry->new, true));
        $this->assertSame($admin->id, $entry->user_id);
    }

    public function test_notification_bell_counts_unread_items_and_mark_all_read_is_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach (range(1, 6) as $number) {
            $owner->notify(new AccountApproved);
        }
        $otherUser->notify(new AccountApproved);

        $this->actingAs($owner)
            ->get('/')
            ->assertSee('6');

        $this->post('/bildirishnomalar/hammasi-oqildi')->assertRedirect();

        $this->assertSame(0, $owner->unreadNotifications()->count());
        $this->assertSame(1, $otherUser->unreadNotifications()->count());
    }

    public function test_admin_audit_filters_by_user_action_and_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $actor = User::factory()->create();
        $this->actingAs($actor);
        AuditLog::record('user.role_or_block_updated', null, ['role' => 'student'], ['role' => 'moderator']);
        AuditLog::record('settings.yearly_limit_updated', null, ['yearly_limit' => 6], ['yearly_limit' => 7]);

        $this->actingAs($admin)
            ->get('/admin/audit?user_id='.$actor->id.'&action=role&from='.now()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertSee('user.role_or_block_updated')
            ->assertDontSee('settings.yearly_limit_updated');
    }

    public function test_student_cannot_access_admin_audit_log(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get('/admin/audit')
            ->assertForbidden();
    }
}
