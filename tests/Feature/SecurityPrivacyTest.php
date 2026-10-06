<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DataDeletionRequest;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/')->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_uploads_validate_mime_by_content(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['category' => 'bakalavr']);
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = Journal::create(['name' => 'MIME jurnali', 'field' => 'Tarix', 'tier' => 'B', 'listed_from' => '2019-01-01']);

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'pdf' => UploadedFile::fake()->createWithContent('maqola.php', '<?php echo 1;'),
        ]))->assertSessionHasErrors('pdf');

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'pdf' => UploadedFile::fake()->createWithContent('maqola.pdf', '%PDF-1.4 ok'),
        ]))->assertSessionDoesntHaveErrors('pdf');

        $this->actingAs($admin)->post('/admin/yangiliklar', [
            'title' => 'MIME yangilik', 'body' => 'Matn', 'type' => 'boshqa',
            'attachment' => UploadedFile::fake()->createWithContent('ilova.txt', 'plain text'),
        ])->assertSessionHasErrors('attachment');

        $this->actingAs($user)->post('/profil', [
            'phone' => null, 'telegram' => null,
            'photo' => UploadedFile::fake()->createWithContent('rasm.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_files_are_stored_on_private_disk_only(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['category' => 'bakalavr']);
        $journal = Journal::create(['name' => 'Disk jurnali', 'field' => 'Tarix', 'tier' => 'B', 'listed_from' => '2019-01-01']);

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'journal_name' => $journal->name,
        ]));

        $article = Article::firstOrFail();
        Storage::disk('local')->assertExists($article->pdf_path);
        $this->assertFalse(Storage::disk('public')->exists($article->pdf_path));
    }

    public function test_privacy_page_and_registration_link_are_visible(): void
    {
        $this->get('/maxfiylik')->assertOk()
            ->assertSee('Maxfiylik siyosati')
            ->assertSee('O‘RQ-547');

        $this->get('/royxat')->assertOk()->assertSee('/maxfiylik');
    }

    public function test_deletion_request_flow_anonymizes_user_and_keeps_statistics(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'category' => 'bakalavr',
            'first_name' => 'Anvar',
            'last_name' => 'Karimov',
            'email' => 'anvar@example.uz',
            'photo_path' => 'photos/anvar.jpg',
        ]);
        Storage::disk('local')->put('photos/anvar.jpg', 'fake');
        $journal = Journal::create(['name' => 'Del jurnali', 'field' => 'Tarix', 'tier' => 'B', 'listed_from' => '2019-01-01']);
        Article::create([
            'user_id' => $user->id,
            'title' => 'Del maqola',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => '2025-01-01',
            'url' => 'https://example.uz/del',
            'position' => 'yolgiz',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post('/profil/ochirish-sorovi')->assertRedirect();
        $this->actingAs($user)->post('/profil/ochirish-sorovi')->assertSessionHasErrors('deletion');
        $request = DataDeletionRequest::firstOrFail();

        $this->actingAs($user)->get('/admin/ochirish-sorovlari')->assertForbidden();
        $this->actingAs($admin)->post('/admin/ochirish-sorovlari/'.$request->id, ['decision' => 'done'])->assertRedirect();

        $fresh = $user->fresh();
        $this->assertSame('O‘chirilgan foydalanuvchi', $fresh->name);
        $this->assertSame('deleted-'.$user->id.'@anon.local', $fresh->email);
        $this->assertTrue($fresh->blocked);
        $this->assertNull($fresh->photo_path);
        $this->assertSame(0, $fresh->notifications()->count());
        $this->assertSame(1, $user->articles()->count()); // statistics kept
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'user.deletion_processed')->first());
        Storage::disk('local')->assertMissing('photos/anvar.jpg');
    }

    private function articlePayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'MIME maqola '.uniqid(),
            'annotation_uz' => str_repeat('O‘zbek annotatsiya matni. ', 4),
            'annotation_ru' => str_repeat('Русская аннотация. ', 4),
            'annotation_en' => str_repeat('English abstract text. ', 4),
            'keywords_uz' => 'ilm, fan, maqola',
            'keywords_ru' => 'наука, статья, исследование',
            'keywords_en' => 'science, article, research',
            'type' => 'journal_local_oak',
            'journal_name' => 'MIME jurnali',
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/'.uniqid(),
            'authors' => [['full_name' => 'Muallif F.I.Sh.', 'position' => 'yolgiz', 'is_submitter' => '1']],
            'pdf' => UploadedFile::fake()->createWithContent('maqola.pdf', '%PDF-1.4 ok'),
        ], $overrides);
    }
}
