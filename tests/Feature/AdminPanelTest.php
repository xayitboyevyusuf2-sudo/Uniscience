<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Guide;
use App\Models\Journal;
use App\Models\JournalRequest;
use App\Models\News;
use App\Models\Rating;
use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use App\Services\Scorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoring_coefficient_override_changes_scorer_total_and_writes_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'Koeff jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        Article::create([
            'user_id' => $user->id,
            'title' => 'Koeff maqola',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/koeff',
            'position' => 'yolgiz',
            'status' => 'approved',
        ]);

        $this->assertSame(3.0, Scorer::for($user)['total']); // defaults: 1.0 x 3 x 1.0 x 1.0

        $this->actingAs($admin)->post('/admin/sozlamalar/koeffitsientlar', ['tier' => ['D' => 6]])
            ->assertRedirect();

        $this->assertSame('6', Setting::get('tier.D'));
        $this->assertSame(6.0, Scorer::for($user)['total']);
        $this->assertNotNull(DB::table('setting_changes')->where('key', 'tier.D')->first());
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'settings.scoring_updated')->first());
        // ratings were recalculated with the new coefficient
        $this->assertSame(6.0, (float) Rating::where('user_id', $user->id)->value('score'));

        // without overrides the default behavior is exactly as before
        Setting::where('key', 'tier.D')->delete();
        $this->assertSame(3.0, Scorer::for($user)['total']);
    }

    public function test_scoring_form_validates_ranges(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/sozlamalar/koeffitsientlar', ['position' => ['yolgiz' => 1.5]])
            ->assertSessionHasErrors('position.yolgiz');
        $this->actingAs($admin)->post('/admin/sozlamalar/koeffitsientlar', ['monthly_flag_threshold' => 0])
            ->assertSessionHasErrors('monthly_flag_threshold');
    }

    public function test_students_get_403_on_all_admin_get_routes(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $journal = Journal::create(['name' => 'Marshrut jurnali', 'field' => 'Tarix', 'tier' => 'B', 'listed_from' => '2019-01-01']);
        $news = News::create(['title' => 'Marshrut yangilik', 'body' => 'Matn', 'type' => 'boshqa']);
        $guide = Guide::create(['title' => 'Marshrut hujjat', 'file_path' => 'guides/x.pdf', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 10]);
        $video = Video::create(['title' => 'Marshrut video', 'file_path' => 'videos/x.mp4']);

        foreach ([
            '/admin',
            '/admin/audit',
            '/admin/foydalanuvchilar',
            '/admin/foydalanuvchi/'.$other->id.'/tahrir',
            '/admin/jurnallar',
            '/admin/jurnallar/yangi',
            '/admin/jurnallar/'.$journal->id.'/tahrir',
            '/admin/jurnal-arizalari',
            '/admin/ochirish-sorovlari',
            '/admin/yangiliklar',
            '/admin/yangiliklar/yangi',
            '/admin/yangiliklar/'.$news->id.'/tahrir',
            '/admin/yoriqnoma',
            '/admin/videolar',
        ] as $path) {
            $response = $this->actingAs($student)->get($path);
            $this->assertSame(403, $response->status(), $path.' talabaga 200 qaytardi');
        }
    }

    public function test_admin_can_create_staff_account_with_verified_email_and_null_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/foydalanuvchilar/xodim', [
            'name' => 'Rahbar Xodim',
            'email' => 'rahbar@example.uz',
            'role' => 'rahbariyat',
            'password' => 'kuchli-parol-1',
        ])->assertRedirect();

        $user = User::where('email', 'rahbar@example.uz')->firstOrFail();
        $this->assertSame('rahbariyat', $user->role);
        $this->assertNull($user->category);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'user.staff_created')->first());

        $this->actingAs($admin)->post('/admin/foydalanuvchilar/xodim', [
            'name' => 'Takroriy',
            'email' => 'rahbar@example.uz',
            'role' => 'moderator',
            'password' => 'kuchli-parol-2',
        ])->assertSessionHasErrors('email');
    }

    public function test_journal_request_resolve_creates_journal_and_reverifies_article(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot']);
        $article = Article::create([
            'user_id' => $owner->id,
            'title' => 'Arizali maqola',
            'type' => 'journal_local_oak',
            'journal_name' => 'Noma’lum jurnal',
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/ariza',
            'position' => 'yolgiz',
            'status' => 'manual',
            'reason' => 'Jurnal topilmadi',
        ]);
        $journalRequest = JournalRequest::create([
            'article_id' => $article->id,
            'user_id' => $owner->id,
            'journal_name' => 'Noma’lum jurnal',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->post('/admin/jurnal-arizalari/'.$journalRequest->id.'/hal', [
            'new_name' => 'Noma’lum jurnal',
            'new_field' => ['Iqtisodiyot'],
            'new_tier' => 'D',
            'new_listed_from' => '2019-01-01',
        ])->assertRedirect();

        $this->assertSame('resolved', $journalRequest->fresh()->status);
        $journal = Journal::where('name', 'Noma’lum jurnal')->firstOrFail();
        $article = $article->fresh();
        $this->assertSame($journal->id, $article->journal_id);
        $this->assertSame('approved', $article->status);
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'journal_request.resolved')->first());
        $this->assertSame(Scorer::for($owner)['total'], (float) Rating::where('user_id', $owner->id)->value('score'));
    }

    public function test_journal_request_reject_requires_note_and_audits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $article = Article::create([
            'user_id' => $owner->id,
            'title' => 'Rad arizasi maqolasi',
            'type' => 'journal_local_oak',
            'journal_name' => 'Yoq jurnal',
            'published_at' => '2025-05-01',
            'url' => 'https://example.uz/rad-ariza',
            'position' => 'yolgiz',
            'status' => 'manual',
        ]);
        $journalRequest = JournalRequest::create(['article_id' => $article->id, 'user_id' => $owner->id, 'journal_name' => 'Yoq jurnal', 'status' => 'open']);

        $this->actingAs($admin)->post('/admin/jurnal-arizalari/'.$journalRequest->id.'/rad', [])
            ->assertSessionHasErrors('note');
        $this->actingAs($admin)->post('/admin/jurnal-arizalari/'.$journalRequest->id.'/rad', ['note' => 'OAK da yo‘q'])
            ->assertRedirect();

        $this->assertSame('rejected', $journalRequest->fresh()->status);
        $this->assertNotNull(DB::table('audit_logs')->where('action', 'journal_request.rejected')->first());
    }

    public function test_admin_post_actions_are_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'student', 'faculty' => 'F1']);

        $this->actingAs($admin)->post('/admin/foydalanuvchi/'.$target->id, ['role' => 'moderator', 'faculty_ids' => ['F1']]);
        $this->actingAs($admin)->post('/admin/sozlamalar', ['yearly_limit' => 7]);
        $journal = Journal::create(['name' => 'Audit jurnali', 'field' => 'Tarix', 'tier' => 'B', 'listed_from' => '2019-01-01']);
        $this->actingAs($admin)->post('/admin/jurnallar/'.$journal->id, ['name' => 'Audit jurnali', 'field' => ['Tarix'], 'tier' => 'B', 'listed_from' => '2019-01-01']);

        foreach (['user.role_or_block_updated', 'settings.yearly_limit_updated', 'journal.updated'] as $action) {
            $this->assertNotNull(DB::table('audit_logs')->where('action', $action)->first(), $action.' yozilmadi');
        }
    }
}
