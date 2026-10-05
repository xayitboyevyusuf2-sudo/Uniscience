<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_profile_and_upload_photo()
    {
        Storage::fake();
        $u = User::factory()->create(['direction' => 'Iqtisodiyot', 'faculty' => 'F', 'course' => 3, 'category' => 'bakalavr']);
        $originalName = $u->name;
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $this->actingAs($u)->post('/profil', ['first_name' => 'Ali', 'last_name' => 'Valiyev', 'course' => 4, 'group_name' => 'IF-04', 'phone' => '+998901234567', 'telegram' => '@elbek', 'bio' => 'Salom dunyo', 'photo' => UploadedFile::fake()->createWithContent('a.png', $png)]);
        $u->refresh();
        $this->assertSame($originalName, $u->name);
        $this->assertSame(4, $u->course);
        $this->assertSame('IF-04', $u->group_name);
        $this->assertSame('+998901234567', $u->phone);
        $this->assertSame('@elbek', $u->telegram);
        $this->assertNotNull($u->photo_path);
        $this->assertMatchesRegularExpression('/^photos\\/[A-Za-z0-9]{40}\\.png$/', $u->photo_path);
        Storage::assertExists($u->photo_path);
        $this->actingAs($u)->get('/foto/'.$u->id)->assertOk();
        $this->actingAs($u)->get('/talaba/'.$u->id)->assertOk()->assertSee('Salom dunyo')->assertSee('Bildirishnomalar');
    }

    public function test_profile_pages_require_login()
    {
        $this->get('/profil')->assertRedirect('/kirish');
        $this->get('/talaba/1')->assertRedirect('/kirish');
    }

    public function test_student_cannot_change_name_fields_from_profile_form()
    {
        $u = User::factory()->create(['first_name' => 'Original', 'last_name' => 'Student', 'name' => 'Original Student', 'category' => 'bakalavr']);
        $this->actingAs($u)->post('/profil', ['first_name' => 'Injected', 'last_name' => 'Value', 'patronymic' => 'Unexpected', 'university' => 'Other university', 'course' => 4]);
        $u->refresh();
        $this->assertSame('Original', $u->first_name);
        $this->assertSame('Student', $u->last_name);
        $this->assertSame('Original Student', $u->name);
    }

    public function test_profile_photo_rejects_six_mb_accepts_four_mb_and_is_square_when_gd_is_available()
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is required for the square crop assertion.');
        }
        Storage::fake('local');
        $u = User::factory()->create(['category' => 'bakalavr', 'course' => 3]);
        $large = UploadedFile::fake()->image('large.jpg', 600, 400)->size(6144);
        $this->actingAs($u)->post('/profil', ['course' => 4, 'photo' => $large])->assertSessionHasErrors('photo');
        $valid = UploadedFile::fake()->image('valid.jpg', 600, 400)->size(4096);
        $this->actingAs($u)->post('/profil', ['course' => 4, 'photo' => $valid])->assertRedirect('/talaba/'.$u->id);
        $u->refresh();
        $this->assertNotNull($u->photo_path);
        Storage::disk('local')->assertExists($u->photo_path);
        $dimensions = getimagesizefromstring(Storage::disk('local')->get($u->photo_path));
        $this->assertSame([512, 512], [$dimensions[0], $dimensions[1]]);
    }

    public function test_profile_password_change_requires_the_current_password()
    {
        $u = User::factory()->create(['password' => 'currentPass123']);
        $this->actingAs($u)->post('/profil', ['current_password' => 'wrongPass123', 'password' => 'nextPass123', 'password_confirmation' => 'nextPass123'])
            ->assertSessionHasErrors(['current_password']);
        $this->assertTrue(password_verify('currentPass123', $u->fresh()->password));
    }

    public function test_admin_profile_edit_updates_name_username_and_audit_log()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u = User::factory()->create(['first_name' => 'Old', 'last_name' => 'Name', 'patronymic' => 'Oldov', 'name' => 'Old Name', 'username' => 'Name Old Oldov', 'university' => 'Old university', 'faculty' => 'Old faculty', 'direction' => 'Iqtisodiyot']);
        $this->actingAs($admin)->post('/admin/foydalanuvchi/'.$u->id.'/tahrir', [
            'first_name' => 'New', 'last_name' => 'Student', 'patronymic' => 'Familyevich', 'university' => 'New university', 'faculty' => 'Yangi fakultet', 'direction' => 'Biologiya', 'group_name' => 'BIO-01',
        ])->assertRedirect('/talaba/'.$u->id);
        $u->refresh();
        $this->assertSame('New Student', $u->name);
        $this->assertSame('Student New Familyevich', $u->username);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'user.profile_updated', 'subject_id' => $u->id]);
        $entry = DB::table('audit_logs')->where('action', 'user.profile_updated')->first();
        $this->assertSame('Old Name', json_decode($entry->old, true)['name']);
        $this->assertSame('New Student', json_decode($entry->new, true)['name']);
    }

    public function test_replacing_profile_photo_deletes_the_previous_private_file()
    {
        Storage::fake('local');
        $u = User::factory()->create(['category' => 'bakalavr', 'course' => 3]);
        $this->actingAs($u)->post('/profil', ['course' => 3, 'photo' => UploadedFile::fake()->image('first.png', 40, 30)])->assertRedirect('/talaba/'.$u->id);
        $oldPath = $u->fresh()->photo_path;
        $this->actingAs($u)->post('/profil', ['course' => 3, 'photo' => UploadedFile::fake()->image('second.png', 50, 30)])->assertRedirect('/talaba/'.$u->id);
        $newPath = $u->fresh()->photo_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }

    public function test_admin_profile_edit_rejects_duplicate_username_case_insensitively()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = User::factory()->create(['first_name' => 'Ali', 'last_name' => 'Valiyev', 'patronymic' => 'Anvarovich', 'username' => 'Valiyev Ali Anvarovich']);
        $target = User::factory()->create(['first_name' => 'New', 'last_name' => 'Student', 'patronymic' => 'Familyevich', 'username' => 'Student New Familyevich']);
        $this->actingAs($admin)->post('/admin/foydalanuvchi/'.$target->id.'/tahrir', [
            'first_name' => 'ALI', 'last_name' => 'VALIYEV', 'patronymic' => 'ANVAROVICH', 'university' => 'Toshkent davlat universiteti', 'faculty' => 'F', 'direction' => 'Iqtisodiyot', 'group_name' => 'G',
        ])->assertSessionHasErrors('first_name');
        $this->assertSame('Student New Familyevich', $target->fresh()->username);
        $this->assertModelExists($existing);
    }

    public function test_student_cannot_open_admin_profile_editor(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $target = User::factory()->create();

        $this->actingAs($student)
            ->get('/admin/foydalanuvchi/'.$target->id.'/tahrir')
            ->assertForbidden();
    }

    public function test_mentor_profile_shows_academic_details_to_authenticated_users(): void
    {
        $viewer = User::factory()->create();
        $mentor = User::factory()->create([
            'category' => 'professor',
            'academic_degree' => 'PhD',
            'position_title' => 'Dotsent',
            'department' => 'Biologiya kafedrasi',
        ]);

        $this->actingAs($viewer)
            ->get('/talaba/'.$mentor->id)
            ->assertOk()
            ->assertSee('PhD')
            ->assertSee('Dotsent')
            ->assertSee('Biologiya kafedrasi');
    }
}
