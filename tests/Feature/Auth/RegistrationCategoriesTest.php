<?php

namespace Tests\Feature\Auth;

use App\Integrations\Hemis\HemisAuthAdapter;
use App\Integrations\Hemis\NullHemisAdapter;
use App\Models\User;
use App\Notifications\AccountApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class RegistrationCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bachelor_registration_creates_full_name_username_and_preserves_display_name(): void
    {
        $this->post('/royxat', $this->studentPayload());

        $this->assertDatabaseHas('users', [
            'email' => 'elbek@example.uz',
            'category' => 'bakalavr',
            'name' => 'Elbek Karimov',
            'username' => 'Karimov Elbek Anvarovich',
            'approval_status' => 'approved',
        ]);
        $this->assertSame('Karimov Elbek Anvarovich', User::where('email', 'elbek@example.uz')->firstOrFail()->fullName());
        $this->assertAuthenticated();
    }

    public function test_registration_ignores_username_from_the_request(): void
    {
        $this->post('/royxat', $this->studentPayload(['username' => 'nik']));

        $this->assertDatabaseHas('users', [
            'email' => 'elbek@example.uz',
            'username' => 'Karimov Elbek Anvarovich',
        ]);
        $this->assertDatabaseMissing('users', ['username' => 'nik']);
    }

    public function test_professor_registration_waits_for_approval_and_cannot_log_in_while_pending(): void
    {
        $this->post('/royxat', $this->academicPayload('professor'))
            ->assertRedirect('/tasdiq-kutilmoqda');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'aziza@example.uz',
            'category' => 'professor',
            'approval_status' => 'pending',
            'student_id' => null,
        ]);

        $this->post('/kirish', ['email' => 'aziza@example.uz', 'password' => 'validPass123'])
            ->assertSessionHasErrors(['email' => 'Admin tasdig‘i kutilmoqda. Tasdiqlangach, tizimga kirishingiz mumkin.']);
        $this->assertGuest();
    }

    public function test_admin_approval_sends_notification_and_allows_login(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Aziza Olimova',
            'first_name' => 'Aziza',
            'last_name' => 'Olimova',
            'patronymic' => 'Rustamovna',
            'category' => 'professor',
            'approval_status' => 'pending',
            'password' => 'validPass123',
        ]);

        $this->actingAs($admin)
            ->post("/admin/foydalanuvchi/{$user->id}/tasdiq", ['decision' => 'approve'])
            ->assertSessionHas('ok', 'Foydalanuvchi tasdiqlandi.');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'approval_status' => 'approved']);
        Notification::assertSentTo($user, AccountApproved::class);

        $this->post('/chiqish');
        $this->post('/kirish', ['email' => $user->email, 'password' => 'validPass123'])
            ->assertRedirect('/portfel');
        $this->assertAuthenticatedAs($user);
    }

    public function test_non_admin_cannot_approve_pending_users(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $user = User::factory()->create(['approval_status' => 'pending']);

        $this->actingAs($moderator)
            ->post("/admin/foydalanuvchi/{$user->id}/tasdiq", ['decision' => 'approve'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'approval_status' => 'pending']);
    }

    public function test_rejected_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['approval_status' => 'rejected', 'password' => 'validPass123']);

        $this->post('/kirish', ['email' => $user->email, 'password' => 'validPass123'])
            ->assertSessionHasErrors(['email' => 'Arizangiz rad etilgan. Batafsil ma’lumot uchun administratorga murojaat qiling.']);

        $this->assertGuest();
    }

    public function test_approved_but_blocked_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved', 'blocked' => true, 'password' => 'validPass123']);

        $this->post('/kirish', ['email' => $user->email, 'password' => 'validPass123'])
            ->assertSessionHasErrors(['email' => 'Email yoki parol noto‘g‘ri']);

        $this->assertGuest();
    }

    public function test_registration_rejects_categories_outside_the_four_allowed_values(): void
    {
        $this->post('/royxat', $this->studentPayload(['category' => 'admin']))
            ->assertSessionHasErrors(['category' => 'Tanlangan toifa noto‘g‘ri.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_helpers_use_profile_category_and_access_role(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Elbek',
            'last_name' => 'Karimov',
            'patronymic' => 'Anvarovich',
            'category' => 'tadqiqotchi',
            'role' => 'rahbariyat',
        ]);

        $this->assertSame('Karimov Elbek Anvarovich', $user->fullName());
        $this->assertFalse($user->isStudent());
        $this->assertTrue($user->isMentor());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isModerator());
        $this->assertTrue($user->isLeadership());
    }

    public function test_duplicate_full_name_is_rejected_case_insensitively(): void
    {
        $this->post('/royxat', $this->studentPayload());
        $this->post('/chiqish');

        $this->post('/royxat', $this->studentPayload([
            'first_name' => 'elbek',
            'last_name' => 'karimov',
            'patronymic' => 'anvarovich',
            'email' => 'different@example.uz',
            'student_id' => 'student-2',
        ]))->assertSessionHasErrors([
            'username' => 'Bu F.I.Sh. bilan foydalanuvchi mavjud. Agar bu siz bo‘lsangiz, kirish sahifasidan foydalaning yoki administratorga murojaat qiling.',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_requires_a_letter_and_number(): void
    {
        $this->post('/royxat', $this->studentPayload([
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]))->assertSessionHasErrors([
            'password' => 'Parolda kamida bitta harf bo‘lishi kerak.',
        ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_requires_a_number(): void
    {
        $this->post('/royxat', $this->studentPayload([
            'password' => 'abcdefgh',
            'password_confirmation' => 'abcdefgh',
        ]))->assertSessionHasErrors([
            'password' => 'Parolda kamida bitta raqam bo‘lishi kerak.',
        ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_requires_at_least_eight_characters(): void
    {
        $this->post('/royxat', $this->studentPayload([
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]))->assertSessionHasErrors([
            'password' => 'Parol kamida 8 belgidan iborat bo‘lishi kerak.',
        ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_masters_registration_requires_student_fields_and_allows_optional_bachelor_university(): void
    {
        $this->post('/royxat', $this->studentPayload([
            'category' => 'magistr',
            'bachelor_university' => '',
            'group_name' => '',
        ]))->assertSessionHasErrors(['group_name' => 'Guruhni kiriting.']);

        $this->post('/royxat', $this->studentPayload([
            'category' => 'magistr',
            'email' => 'master@example.uz',
            'student_id' => 'master-1',
        ]))->assertRedirect('/portfel');

        $this->assertDatabaseHas('users', [
            'email' => 'master@example.uz',
            'category' => 'magistr',
            'bachelor_university' => null,
        ]);
    }

    public function test_researcher_requires_academic_fields_without_student_id(): void
    {
        $payload = $this->academicPayload('tadqiqotchi');
        unset($payload['academic_degree']);

        $this->post('/royxat', $payload)->assertSessionHasErrors(['academic_degree' => 'Ilmiy darajani kiriting.']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_form_shows_category_choices_without_a_username_field(): void
    {
        $this->get('/royxat')
            ->assertSee('Bakalavr')
            ->assertSee('Magistr')
            ->assertSee('Tadqiqotchi')
            ->assertSee('Professor')
            ->assertDontSee('name="username"', false);
    }

    public function test_hemis_fields_are_present_and_registration_sends_no_http_requests(): void
    {
        Http::fake();
        Http::preventStrayRequests();

        $this->post('/royxat', $this->studentPayload());

        foreach (['patronymic', 'username', 'category', 'university', 'group_name', 'gpa', 'birth_date', 'bachelor_university', 'academic_degree', 'position_title', 'department', 'hemis_id', 'auth_provider', 'approval_status'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column));
        }

        $this->assertDatabaseHas('users', [
            'email' => 'elbek@example.uz',
            'hemis_id' => null,
            'auth_provider' => 'local',
        ]);
        $this->assertFalse(config('uniscience.hemis.enabled'));
        $this->assertInstanceOf(NullHemisAdapter::class, app(HemisAuthAdapter::class));
        Http::assertNothingSent();
    }

    public function test_null_hemis_adapter_throws_without_an_http_call(): void
    {
        Http::fake();
        Http::preventStrayRequests();
        $adapter = app(HemisAuthAdapter::class);

        try {
            $adapter->authorizationUrl();
            $this->fail('The null adapter must not activate HEMIS.');
        } catch (RuntimeException $exception) {
            $this->assertSame('HEMIS integratsiyasi 2-bosqichda', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    private function studentPayload(array $overrides = []): array
    {
        return array_replace([
            'category' => 'bakalavr',
            'first_name' => 'Elbek',
            'last_name' => 'Karimov',
            'patronymic' => 'Anvarovich',
            'university' => 'Toshkent davlat universiteti',
            'faculty' => 'Iqtisodiyot fakulteti',
            'direction' => 'Iqtisodiyot',
            'course' => 3,
            'group_name' => 'IF-301',
            'gpa' => '4.25',
            'birth_date' => '2001-01-02',
            'student_id' => 'student-1',
            'email' => 'elbek@example.uz',
            'password' => 'validPass123',
            'password_confirmation' => 'validPass123',
            'consent' => 1,
        ], $overrides);
    }

    private function academicPayload(string $category): array
    {
        return [
            'category' => $category,
            'first_name' => 'Aziza',
            'last_name' => 'Olimova',
            'patronymic' => 'Rustamovna',
            'academic_degree' => 'PhD',
            'position_title' => 'Dotsent',
            'department' => 'Biologiya kafedrasi',
            'university' => 'Toshkent davlat universiteti',
            'direction' => 'Biologiya',
            'email' => 'aziza@example.uz',
            'password' => 'validPass123',
            'password_confirmation' => 'validPass123',
            'consent' => 1,
        ];
    }
}
