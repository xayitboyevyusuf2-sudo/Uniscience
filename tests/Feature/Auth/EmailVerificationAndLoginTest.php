<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailUz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationAndLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_case_insensitive_email(): void
    {
        $user = User::factory()->create(['email' => 'login@example.uz']);

        $this->post('/kirish', ['login' => 'LOGIN@EXAMPLE.UZ', 'password' => 'password'])
            ->assertRedirect('/portfel');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_log_in_with_case_insensitive_username(): void
    {
        $user = User::factory()->create(['username' => 'Karimov Elbek']);

        $this->post('/kirish', ['login' => 'KARIMOV ELBEK', 'password' => 'password'])
            ->assertRedirect('/portfel');

        $this->assertAuthenticatedAs($user);
    }

    public function test_five_wrong_passwords_lock_login_for_fifteen_minutes(): void
    {
        $user = User::factory()->create(['email' => 'locked@example.uz', 'password' => 'correctPass123']);
        $this->travelTo(now());

        for ($attempt = 1; $attempt < 5; $attempt++) {
            $this->post('/kirish', ['login' => 'LOCKED@EXAMPLE.UZ', 'password' => 'wrongPass123'])
                ->assertSessionHasErrors(['login' => 'Login yoki parol noto‘g‘ri.']);
        }

        $this->post('/kirish', ['login' => 'locked@example.uz', 'password' => 'wrongPass123'])
            ->assertSessionHasErrors(['login' => 'Juda ko‘p noto‘g‘ri urinish bo‘ldi. 15 daqiqadan so‘ng urinib ko‘ring.']);

        $this->travel(14)->minutes();
        $this->post('/kirish', ['login' => $user->email, 'password' => 'correctPass123'])
            ->assertSessionHasErrors(['login' => 'Juda ko‘p noto‘g‘ri urinish bo‘ldi. 1 daqiqadan so‘ng urinib ko‘ring.']);

        $this->travel(1)->minutes();
        $this->post('/kirish', ['login' => $user->email, 'password' => 'correctPass123'])
            ->assertRedirect('/portfel');

        $this->assertAuthenticatedAs($user);

        $this->post('/chiqish');
        $this->post('/kirish', ['login' => $user->email, 'password' => 'wrongPass123'])
            ->assertSessionHasErrors(['login' => 'Login yoki parol noto‘g‘ri.']);
    }

    public function test_unverified_user_is_redirected_from_upload_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/yuklash')
            ->assertRedirect('/email/tasdiqlash');
    }

    public function test_signed_verification_link_marks_email_verified_and_opens_profile(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = (new VerifyEmailUz)->toMail($user)->actionUrl;

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect('/profil');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/profil')->assertOk();
    }

    public function test_authenticated_user_can_resend_verification_email_with_throttling(): void
    {
        Notification::fake([VerifyEmailUz::class]);
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/email/tasdiqlash')->assertRedirect();
        }

        $this->post('/email/tasdiqlash')->assertTooManyRequests();
        Notification::assertSentTo($user, VerifyEmailUz::class);
    }

    public function test_remember_cookie_expires_after_thirty_days(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['email' => 'remember@example.uz']);

        $response = $this->post('/kirish', [
            'login' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $cookie = $response->getCookie(Auth::guard('web')->getRecallerName());

        $this->assertNotNull($cookie);
        $this->assertSame(now()->addMinutes(43200)->timestamp, $cookie->getExpiresTime());
    }

    public function test_successful_login_keeps_at_most_three_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $this->app['session']->forgetDrivers();
        $user = User::factory()->create(['email' => 'devices@example.uz']);

        foreach (range(1, 4) as $number) {
            DB::table('sessions')->insert([
                'id' => 'old-session-'.$number,
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => base64_encode('payload-'.$number),
                'last_activity' => now()->subMinutes($number)->timestamp,
            ]);
        }

        $this->post('/kirish', ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect('/portfel');

        $this->assertSame(3, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session-4']);
    }

    public function test_login_and_registration_forms_offer_password_visibility_toggle(): void
    {
        $this->get('/kirish')
            ->assertSee('data-password-toggle', false)
            ->assertSee('Eslab qolish');

        $this->get('/royxat')->assertSee('data-password-toggle', false);

        $this->get('/parolni-tiklash/test-token?email=test@example.uz')
            ->assertSee('data-password-toggle', false);
    }
}
