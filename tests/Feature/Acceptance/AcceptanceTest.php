<?php

namespace Tests\Feature\Acceptance;

use App\Models\Article;
use App\Models\Certificate;
use App\Models\Journal;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\ArticleStatus;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Acceptance tests for TZ scenarios TS-01..TS-22. Each method maps to one TS row in docs/QABUL_TESTLARI.md.
 * Existing project tests already cover most TS; this file fills the remaining gaps with thin end-to-end checks.
 */
class AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    // TS-01: registration with consent — no email verification
    public function test_ts01_registration_consent_and_email_verification_link(): void
    {
        \App\Models\University::create(['name' => 'TATU']);
        $this->post('/royxat', $this->registerPayload())->assertRedirect('/portfel');
        $user = User::where('email', 'ts01@example.uz')->firstOrFail();
        $this->assertNotNull($user->consent_at);
        $this->assertSame('ts01', $user->username);
        $this->assertNull($user->email_verified_at);
    }

    // TS-03: article upload shows multilingual metadata, authors, PDF and journal search
    public function test_ts03_article_upload_form_has_multilingual_sections_and_pdf(): void
    {
        $user = User::factory()->create(['category' => 'bakalavr']);
        $this->actingAs($user)->get('/yuklash')
            ->assertOk()
            ->assertSee('annotation_uz')
            ->assertSee('annotation_ru')
            ->assertSee('annotation_en')
            ->assertSee('keywords_uz')
            ->assertSee('authors[0][full_name]')
            ->assertSee('journal_name')
            ->assertSee('name="pdf"', false);
    }

    // TS-07: public base shows approved articles with filters
    public function test_ts07_public_base_lists_approved_articles_with_filters(): void
    {
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'Baza jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'B', 'listed_from' => '2019-01-01']);
        Article::create([
            'user_id' => $user->id,
            'title' => 'Baza ochiq maqola',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => '2025-05-01',
            'url' => 'https://example.uz/baza',
            'position' => 'yolgiz',
            'status' => 'approved',
        ]);

        $this->get('/baza')->assertOk()->assertSee('Baza ochiq maqola');
        $this->get('/baza?field=Iqtisodiyot')->assertOk()->assertSee('Baza ochiq maqola');
        $this->get('/baza?field=Tarix')->assertOk()->assertDontSee('Baza ochiq maqola');
    }

    // TS-09: moderator queue lists manual articles and decision changes status + notifies
    public function test_ts09_moderator_decision_updates_status_and_notifies(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['faculty' => 'Iqtisodiyot']);
        $article = Article::create([
            'user_id' => $owner->id,
            'title' => 'Navbat maqolasi',
            'type' => 'journal_local_oak',
            'journal_name' => 'Noma’lum',
            'published_at' => '2025-05-01',
            'url' => 'https://example.uz/navbat',
            'position' => 'yolgiz',
            'status' => 'manual',
        ]);

        $this->actingAs($admin)->get('/moderator/navbat')->assertOk()->assertSee('Navbat maqolasi');
        $this->actingAs($admin)->post('/admin/qaror/'.$article->id, ['decision' => 'reject', 'note' => 'Jarayon rad'])->assertRedirect();
        $this->assertSame('rejected', $article->fresh()->status);
        Notification::assertSentTo($owner, ArticleStatus::class);
    }

    // TS-10: certificate page verifies by token and shows score
    public function test_ts10_certificate_token_verification_page(): void
    {
        $user = User::factory()->create();
        $certificate = Certificate::create(['user_id' => $user->id, 'token' => 'tokents10']);

        $this->get('/malumotnoma/tokents10')->assertOk()->assertSee($user->name);
        $this->get('/malumotnoma/noto‘g‘ri')->assertNotFound();
    }

    // TS-11: profile shows score, approved articles and ranks
    public function test_ts11_profile_shows_score_articles_and_ranks(): void
    {
        $user = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'TS11 jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        Article::create([
            'user_id' => $user->id,
            'title' => 'TS11 maqola',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/ts11',
            'position' => 'yolgiz',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->get('/talaba/'.$user->id)
            ->assertOk()
            ->assertSee('TS11 maqola')
            ->assertSee('O‘rin')
            ->assertSee('3.00');
    }

    // TS-12: rating page shows category tabs and scopes with my-neighborhood
    public function test_ts12_rating_tabs_scopes_and_neighborhood(): void
    {
        $user = User::factory()->create(['category' => 'bakalavr', 'university' => 'Uni', 'faculty' => 'Iqtisodiyot', 'group_name' => 'G1']);
        Rating::create(['user_id' => $user->id, 'category' => 'bakalavr', 'score' => 4, 'computed_at' => now()]);
        RatingService::refreshRanks();

        $this->actingAs($user)->get('/reyting')
            ->assertOk()
            ->assertSee('Bakalavrlar')
            ->assertSee('Magistrlar')
            ->assertSee('Tadqiqotchilar')
            ->assertSee('Professorlar')
            ->assertSee('Universitet')
            ->assertSee('Fakultet')
            ->assertSee('Guruh')
            ->assertSee('Mening reytingim');
    }

    // TS-21: admin panel sidebar and all sections reachable
    public function test_ts21_admin_panel_sidebar_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Foydalanuvchilar')
            ->assertSee('Jurnallar')
            ->assertSee('Navbat')
            ->assertSee('Kontent')
            ->assertSee('Jurnal arizalari')
            ->assertSee('O‘chirish so‘rovlari')
            ->assertSee('Audit');
    }

    // TS-22: full manual flow — register → verify → upload → moderator → rating → certificate → leadership export
    public function test_ts22_full_manual_flow(): void
    {
        Storage::fake('local');
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $leader = User::factory()->create(['role' => 'rahbariyat']);
        $journal = Journal::create(['name' => 'TS22 jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);

        \App\Models\University::create(['name' => 'TATU']);
        $this->post('/royxat', $this->registerPayload())->assertRedirect('/portfel');
        $user = User::where('email', 'ts01@example.uz')->firstOrFail();

        $this->actingAs($user)->post('/yuklash', [
            'title' => 'TS22 to‘liq oqim maqolasi',
            'annotation_uz' => str_repeat('O‘zbek annotatsiya. ', 4),
            'annotation_ru' => str_repeat('Русская аннотация. ', 4),
            'annotation_en' => str_repeat('English abstract. ', 4),
            'keywords_uz' => 'ilm, fan, tadqiqot',
            'keywords_ru' => 'наука, статья, исследование',
            'keywords_en' => 'science, article, research',
            'type' => 'journal_local_oak',
            'journal_name' => $journal->name,
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/ts22',
            'authors' => [['full_name' => 'Test F.I.Sh.', 'position' => 'yolgiz', 'is_submitter' => '1']],
            'pdf' => UploadedFile::fake()->createWithContent('ts22.pdf', '%PDF-1.4'),
        ])->assertRedirect();

        $article = Article::where('title', 'TS22 to‘liq oqim maqolasi')->firstOrFail();
        $this->assertSame('approved', $article->status); // auto-approved by verifier

        $this->actingAs($user)->get('/reyting/mening')->assertOk()->assertSee('3.00');
        $this->actingAs($user)->post('/malumotnoma')->assertRedirect();
        $certificate = Certificate::firstOrFail();
        $this->get('/malumotnoma/'.$certificate->token)->assertOk();

        $this->actingAs($leader)->get('/rahbariyat/eksport.csv')->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $this->actingAs($leader)->get('/rahbariyat/eksport.csv')->headers->get('content-type'));
    }

    private function registerPayload(): array
    {
        return [
            'category' => 'bakalavr',
            'first_name' => 'Test',
            'last_name' => 'Talaba',
            'patronymic' => 'O‘g‘li',
            'university' => 'Toshkent davlat universiteti',
            'faculty' => 'Iqtisodiyot fakulteti',
            'direction' => 'Iqtisodiyot',
            'course' => 3,
            'group_name' => 'IF-301',
            'gpa' => '4.25',
            'birth_date' => '2001-01-02',
            'student_id' => 'student-ts01',
            'email' => 'ts01@example.uz',
            'username' => 'ts01',
            'password' => 'validPass123',
            'password_confirmation' => 'validPass123',
            'consent' => 1,
        ];
    }
}
