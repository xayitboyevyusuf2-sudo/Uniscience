<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuidesVideosTest extends TestCase
{
    use RefreshDatabase;

    // TS-16: students can read/download, cannot upload/delete (403); admin uploads fake PDF;
    // video stream endpoint answers 206 with a Range header
    public function test_ts16_student_reads_and_downloads_but_cannot_manage(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->post('/admin/yoriqnoma', [
            'title' => 'Grant yo‘riqnomasi',
            'category' => 'Grantlar',
            'file' => UploadedFile::fake()->createWithContent('yoriqnoma.pdf', '%PDF-1.4 fake guide content'),
        ])->assertRedirect();
        $guide = Guide::firstOrFail();
        $this->assertSame('application/pdf', $guide->mime);

        $this->actingAs($student)->get('/yoriqnoma')->assertOk()->assertSee('Grant yo‘riqnomasi');
        $this->actingAs($student)->get('/yoriqnoma/hujjat/'.$guide->id)->assertOk()->assertHeader('content-type', 'application/pdf');
        $download = $this->actingAs($student)->get('/yoriqnoma/hujjat/'.$guide->id.'/yuklab-olish')->assertOk();
        $this->assertSame('application/pdf', $download->headers->get('content-type'));

        $this->actingAs($student)->get('/admin/yoriqnoma')->assertForbidden();
        $this->actingAs($student)->get('/admin/videolar')->assertForbidden();
        $this->actingAs($student)->post('/admin/yoriqnoma', [])->assertForbidden();
        $this->actingAs($student)->post('/admin/videolar', [])->assertForbidden();
        $this->actingAs($student)->delete('/admin/yoriqnoma/'.$guide->id)->assertForbidden();

        $this->assertDatabaseHas('guides', ['title' => 'Grant yo‘riqnomasi']);
    }

    public function test_video_streams_with_range_support_and_downloads(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->post('/admin/videolar', [
            'title' => 'Kirish darsi',
            'file' => UploadedFile::fake()->createWithContent('dars.mp4', str_repeat('x', 1000)),
        ])->assertRedirect();
        $video = Video::firstOrFail();

        $this->actingAs($student)->get('/yoriqnoma/video/'.$video->id)->assertOk()
            ->assertSee('controls', false)
            ->assertSee('/yoriqnoma/video/'.$video->id.'/oqim');

        $full = $this->actingAs($student)->get('/yoriqnoma/video/'.$video->id.'/oqim')->assertOk();
        $this->assertSame('video/mp4', $full->headers->get('content-type'));

        $partial = $this->actingAs($student)->get('/yoriqnoma/video/'.$video->id.'/oqim', ['Range' => 'bytes=0-99']);
        $partial->assertStatus(206);
        $this->assertSame('bytes 0-99/1000', $partial->headers->get('content-range'));

        $this->actingAs($student)->get('/yoriqnoma/video/'.$video->id.'/yuklab-olish')->assertOk();
    }

    public function test_views_are_counted_once_per_user_with_new_badge_for_recent_items(): void
    {
        Storage::fake('local');
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $path = 'guides/test.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        $guide = Guide::create([
            'title' => 'Yangi hujjat',
            'file_path' => $path,
            'original_name' => 'hujjat.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'created_at' => now(),
        ]);

        $this->travel(3)->days();
        $this->actingAs($student)->get('/yoriqnoma/hujjat/'.$guide->id)->assertOk();
        $this->actingAs($student)->get('/yoriqnoma/hujjat/'.$guide->id)->assertOk();
        $this->actingAs($other)->get('/yoriqnoma/hujjat/'.$guide->id)->assertOk();

        $this->assertSame(2, DB::table('content_views')->where('viewable_type', (new Guide)->getMorphClass())->where('viewable_id', $guide->id)->count());

        $this->actingAs($student)->get('/yoriqnoma')->assertOk()->assertSee('Yangi');
        $this->travel(5)->days();
        $this->actingAs($student)->get('/yoriqnoma')->assertOk()->assertDontSee('>Yangi<');
    }

    public function test_wrong_mime_is_rejected_and_admin_can_delete_with_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/yoriqnoma', [
            'title' => 'Noto‘g‘ri fayl',
            'file' => UploadedFile::fake()->create('skript.php', 1, 'application/x-php'),
        ])->assertSessionHasErrors('file');

        $this->actingAs($admin)->post('/admin/videolar', [
            'title' => 'Noto‘g‘ri video',
            'file' => UploadedFile::fake()->create('video.avi', 1, 'video/x-msvideo'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('guides', ['title' => 'Noto‘g‘ri fayl']);

        $this->actingAs($admin)->post('/admin/yoriqnoma', [
            'title' => 'O‘chiriladigan hujjat',
            'file' => UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4'),
        ])->assertRedirect();
        $guide = Guide::firstOrFail();
        $path = $guide->file_path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($admin)->delete('/admin/yoriqnoma/'.$guide->id)->assertRedirect();
        $this->assertDatabaseMissing('guides', ['id' => $guide->id]);
        Storage::disk('local')->assertMissing($path);

        $this->assertNotNull(DB::table('audit_logs')->where('action', 'guide.deleted')->first());
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'guide.created')->first());
    }
}
