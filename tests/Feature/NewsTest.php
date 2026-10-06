<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    // TS-17: type filter; expired deadline is hidden from "Faol" but visible in "Arxiv";
    // the daily command sets archived_at
    public function test_ts17_type_filter_active_scope_and_archive_command(): void
    {
        $user = User::factory()->create();
        $this->createNews(['title' => 'Faol tanlov', 'type' => 'tanlov', 'deadline' => now()->addDays(5)->toDateString()]);
        $this->createNews(['title' => 'Faol konferensiya', 'type' => 'konferensiya', 'deadline' => null]);
        $expired = $this->createNews(['title' => 'Muddati o‘tgan stipendiya', 'type' => 'stipendiya', 'deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($user)->get('/yangiliklar')
            ->assertOk()
            ->assertSee('Faol tanlov')
            ->assertSee('Faol konferensiya')
            ->assertDontSee('Muddati o‘tgan stipendiya');

        $this->actingAs($user)->get('/yangiliklar?type=tanlov')
            ->assertOk()
            ->assertSee('Faol tanlov')
            ->assertDontSee('Faol konferensiya');

        $this->actingAs($user)->get('/yangiliklar?tab=arxiv')
            ->assertOk()
            ->assertSee('Muddati o‘tgan stipendiya')
            ->assertDontSee('Faol tanlov');

        $this->assertNull($expired->fresh()->archived_at);
        Artisan::call('news:archive');
        $this->assertNotNull($expired->fresh()->archived_at);
        $this->assertStringContainsString('arxivlandi', Artisan::output());

        $this->actingAs($user)->get('/yangiliklar?q=konferensiya')
            ->assertOk()
            ->assertSee('Faol konferensiya')
            ->assertDontSee('Faol tanlov');
    }

    public function test_pinned_news_appears_first(): void
    {
        $user = User::factory()->create();
        $this->createNews(['title' => 'Oddiy yangi yangilik', 'created_at' => now()]);
        $this->createNews(['title' => 'Yopishtirilgan eski yangilik', 'pinned' => true, 'created_at' => now()->subDays(3)]);

        $response = $this->actingAs($user)->get('/yangiliklar')->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Oddiy yangi yangilik'),
            strpos($content, 'Yopishtirilgan eski yangilik')
        );
    }

    public function test_attachment_download_requires_login_and_works_for_members(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->post('/admin/yangiliklar', [
            'title' => 'Ilovali yangilik',
            'body' => 'Matn',
            'type' => 'tanlov',
            'attachment' => UploadedFile::fake()->createWithContent('dastur.pdf', '%PDF-1.4 news attachment'),
        ])->assertRedirect();
        $news = News::firstOrFail();

        $response = $this->actingAs($student)->get('/yangiliklar/'.$news->id.'/ilova')->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_attachment_download_requires_login(): void
    {
        Storage::fake('local');
        $path = 'news/ilova.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        $news = News::create([
            'title' => 'Ilovali guest yangilik',
            'body' => 'Matn',
            'type' => 'tanlov',
            'attachment_path' => $path,
            'attachment_name' => 'ilova.pdf',
        ]);

        $this->get('/yangiliklar/'.$news->id.'/ilova')->assertRedirect();
    }

    public function test_students_and_moderators_cannot_manage_news(): void
    {
        Storage::fake('local');
        $news = $this->createNews(['title' => 'Himoyalangan yangilik']);

        foreach (['student', 'moderator'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/admin/yangiliklar')->assertForbidden();
            $this->actingAs($user)->post('/admin/yangiliklar', [])->assertForbidden();
            $this->actingAs($user)->post('/admin/yangiliklar/'.$news->id, [])->assertForbidden();
            $this->actingAs($user)->delete('/admin/yangiliklar/'.$news->id)->assertForbidden();
        }

        $this->assertDatabaseHas('news', ['id' => $news->id]);
    }

    public function test_admin_crud_with_audit_and_validation(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/yangiliklar', [
            'title' => 'Noto‘g‘ri havolali',
            'body' => 'Matn',
            'type' => 'boshqa',
            'link' => 'not-a-url',
        ])->assertSessionHasErrors('link');

        $this->actingAs($admin)->post('/admin/yangiliklar', [
            'title' => 'To‘liq yangilik',
            'body' => 'Yangilik matni',
            'type' => 'stipendiya',
            'event_date' => '2026-11-01',
            'deadline' => now()->addDays(10)->toDateString(),
            'link' => 'https://example.uz/elon',
            'pinned' => '1',
        ])->assertRedirect('/admin/yangiliklar');

        $news = News::firstOrFail();
        $this->assertTrue($news->pinned);
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'news.created')->first());

        $this->actingAs($admin)->post('/admin/yangiliklar/'.$news->id, [
            'title' => 'Yangilangan sarlavha',
            'body' => 'Yangilik matni',
            'type' => 'konferensiya',
        ])->assertRedirect('/admin/yangiliklar');

        $news = $news->fresh();
        $this->assertSame('Yangilangan sarlavha', $news->title);
        $this->assertFalse($news->pinned);
        $entry = DB::table('audit_logs')->where('action', 'news.updated')->latest('id')->first();
        $this->assertSame('To‘liq yangilik', json_decode($entry->old, true)['title']);
        $this->assertSame('Yangilangan sarlavha', json_decode($entry->new, true)['title']);

        $this->actingAs($admin)->delete('/admin/yangiliklar/'.$news->id)->assertRedirect();
        $this->assertDatabaseMissing('news', ['id' => $news->id]);
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'news.deleted')->first());
    }

    public function test_home_shows_latest_three_active_news_for_members_only(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 4) as $i) {
            $this->createNews(['title' => 'Yangilik '.$i, 'created_at' => now()->subDays(4 - $i)]);
        }
        $this->createNews(['title' => 'Eski arxiv', 'deadline' => now()->subDays(2)->toDateString()]);

        $response = $this->actingAs($user)->get('/')->assertOk();
        $response->assertSee('So‘nggi yangiliklar');
        $response->assertSee('Yangilik 4');
        $response->assertSee('Yangilik 3');
        $response->assertSee('Yangilik 2');
        $response->assertDontSee('Eski arxiv');
        $this->assertStringNotContainsString('>Yangilik 1<', $response->getContent());
    }

    public function test_home_hides_news_block_for_guests(): void
    {
        $this->get('/')->assertOk()->assertDontSee('So‘nggi yangiliklar');
    }

    private function createNews(array $overrides = []): News
    {
        return News::create(array_merge([
            'title' => 'Test yangilik',
            'body' => 'Yangilik matni',
            'type' => 'boshqa',
        ], $overrides));
    }
}
