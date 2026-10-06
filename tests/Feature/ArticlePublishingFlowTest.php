<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ConferenceCertificate;
use App\Models\Journal;
use App\Models\User;
use App\Services\Scorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArticlePublishingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_scopus_and_conference_articles_use_forced_tiers_without_journal_models(): void
    {
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $scopusQ1Q2 = $this->createApprovedArticle($user, ['type' => 'scopus_q12', 'journal_name' => 'Q1 venue']);
        $scopusQ3Q4 = $this->createApprovedArticle($user, ['type' => 'scopus_q34_wos', 'journal_name' => 'Q3 venue']);
        $conference = $this->createApprovedArticle($user, ['type' => 'conf_local', 'journal_name' => 'Conference venue']);

        $this->assertSame('A', $scopusQ1Q2->effectiveTier());
        $this->assertSame('B', $scopusQ3Q4->effectiveTier());
        $this->assertSame('E', $conference->effectiveTier());
        $this->assertSame('Iqtisodiyot', $conference->effectiveField());

        $score = Scorer::for($user);
        $this->assertSame(18.0, $score['total']);
        $this->assertTrue($score['rows'][$scopusQ1Q2->id]['counted']);
    }

    public function test_journal_less_articles_from_different_venues_do_not_get_diversity_discount(): void
    {
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $this->createApprovedArticle($user, ['journal_name' => 'Scopus venue A']);
        $this->createApprovedArticle($user, ['journal_name' => 'Scopus venue B']);

        $score = Scorer::for($user);

        $this->assertSame(20.0, $score['total']);
        $this->assertFalse($score['diversity']);
    }

    public function test_journal_less_articles_from_the_same_normalized_venue_get_diversity_discount(): void
    {
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $this->createApprovedArticle($user, ['journal_name' => 'Same Venue!']);
        $this->createApprovedArticle($user, ['journal_name' => 'same-venue']);

        $score = Scorer::for($user);

        $this->assertSame(16.0, $score['total']);
        $this->assertTrue($score['diversity']);
    }

    public function test_journal_search_requires_authentication_and_finds_by_name_or_issn_prefix(): void
    {
        Journal::create([
            'issn' => '1234-5678',
            'name' => 'OAK Biologiya Jurnali',
            'field' => 'Biologiya',
            'tier' => 'B',
            'listed_from' => '2019-01-01',
        ]);

        $this->getJson('/api/jurnallar/qidiruv?q=OAK')->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/jurnallar/qidiruv?q=1234')
            ->assertOk()
            ->assertJsonPath('0.issn', '1234-5678')
            ->assertJsonPath('0.tier', 'B');

        $this->getJson('/api/jurnallar/qidiruv?q=12345')
            ->assertOk()
            ->assertJsonPath('0.issn', '1234-5678');

        $this->getJson('/api/jurnallar/qidiruv?q=Biologiya')
            ->assertOk()
            ->assertJsonPath('0.name', 'OAK Biologiya Jurnali');
    }

    public function test_selected_journal_is_verified_by_hint_and_approved(): void
    {
        Storage::fake('local');
        $journal = Journal::create([
            'issn' => '2345-6789',
            'name' => 'Tanlangan OAK jurnali',
            'field' => 'Iqtisodiyot',
            'tier' => 'A',
            'listed_from' => '2019-01-01',
        ]);
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'journal_name' => 'Tanlangan OAK jurnali',
            'issn' => '2345-6789',
            'journal_id' => $journal->id,
            'url' => 'https://example.uz/articles/selected-journal',
        ]))->assertRedirect();

        $article = Article::where('url', 'https://example.uz/articles/selected-journal')->firstOrFail();
        $this->assertSame('approved', $article->status);
        $this->assertSame($journal->id, $article->journal_id);
        $this->assertNotNull($article->decided_at);
    }

    public function test_unlisted_journal_checkbox_creates_open_request_and_sets_manual_status(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'journal_name' => 'Ro‘yxatda bo‘lmagan yangi jurnal',
            'journal_not_listed' => '1',
            'url' => 'https://example.uz/articles/unlisted-journal',
        ]))->assertRedirect();

        $article = Article::where('url', 'https://example.uz/articles/unlisted-journal')->firstOrFail();
        $this->assertSame('manual', $article->status);
        $this->assertDatabaseHas('journal_requests', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'journal_name' => 'Ro‘yxatda bo‘lmagan yangi jurnal',
            'status' => 'open',
        ]);
    }

    public function test_verifier_miss_automatically_creates_open_journal_request(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'journal_name' => 'Verifier topmagan jurnal',
            'url' => 'https://example.uz/articles/verifier-miss',
        ]))->assertRedirect();

        $article = Article::where('url', 'https://example.uz/articles/verifier-miss')->firstOrFail();
        $this->assertSame('manual', $article->status);
        $this->assertDatabaseHas('journal_requests', [
            'article_id' => $article->id,
            'status' => 'open',
        ]);
    }

    public function test_scopus_article_skips_oak_verifier_and_requires_field(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $payload = $this->articlePayload([
            'type' => 'scopus_q12',
            'field' => 'Iqtisodiyot',
            'url' => 'https://example.uz/articles/scopus',
        ]);

        unset($payload['field']);
        $this->actingAs($user)->post('/yuklash', $payload)->assertSessionHasErrors('field');
        $this->assertDatabaseCount('articles', 0);

        $payload['field'] = 'Iqtisodiyot';
        $this->actingAs($user)->post('/yuklash', $payload)->assertRedirect();

        $article = Article::where('url', 'https://example.uz/articles/scopus')->firstOrFail();
        $this->assertSame('manual', $article->status);
        $this->assertStringContainsString('Scopus/WoS', $article->reason);
        $this->assertNull($article->journal_id);
    }

    public function test_moderator_can_approve_scopus_article_without_selecting_oak_journal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $article = $this->createApprovedArticle($user, [
            'type' => 'scopus_q12',
            'status' => 'manual',
            'journal_id' => null,
            'reason' => 'Scopus/WoS nashri OAK avtomatik tekshiruvidan o‘tkazilmaydi. Moderator tekshiradi.',
        ]);

        $this->actingAs($admin)
            ->post('/admin/qaror/'.$article->id, ['decision' => 'approve'])
            ->assertSessionHas('ok');

        $article->refresh();
        $this->assertSame('approved', $article->status);
        $this->assertNull($article->journal_id);
        $this->assertNotNull($article->decided_at);
        $this->assertSame(10.0, Scorer::for($user)['total']);
    }

    public function test_conference_requires_certificate_and_stores_it_privately(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $payload = $this->articlePayload([
            'type' => 'conf_local',
            'field' => 'Biologiya',
            'url' => 'https://example.uz/articles/conference',
        ]);
        unset($payload['conference_certificate']);

        $this->actingAs($user)->post('/yuklash', $payload)->assertSessionHasErrors('conference_certificate');
        $this->assertDatabaseCount('articles', 0);

        $payload['conference_certificate'] = UploadedFile::fake()->create('certificate.pdf', 256, 'application/pdf');
        $this->actingAs($user)->post('/yuklash', $payload)->assertRedirect();

        $article = Article::where('url', 'https://example.uz/articles/conference')->firstOrFail();
        $certificate = ConferenceCertificate::where('article_id', $article->id)->firstOrFail();
        $this->assertSame('manual', $article->status);
        $this->assertSame('pending', $certificate->status);
        Storage::disk('local')->assertExists($certificate->path);
    }

    public function test_conference_certificate_over_ten_megabytes_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'type' => 'conf_intl',
            'field' => 'Biologiya',
            'conference_certificate' => UploadedFile::fake()->create('certificate.pdf', 10241, 'application/pdf'),
        ]))->assertSessionHasErrors('conference_certificate');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_article_upload_form_exposes_all_type_family_sections(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/yuklash')
            ->assertOk()
            ->assertSee('data-article-family="journal"', false)
            ->assertSee('data-article-family="scopus"', false)
            ->assertSee('data-article-family="conference"', false)
            ->assertSee('data-add-author', false);
    }

    public function test_conference_article_cannot_be_approved_until_its_certificate_is_verified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $article = $this->createApprovedArticle($user, [
            'type' => 'conf_intl',
            'status' => 'manual',
            'journal_id' => null,
        ]);
        $certificate = ConferenceCertificate::create(['article_id' => $article->id, 'path' => 'conference-certificates/test.pdf']);

        $this->actingAs($admin)
            ->post('/admin/qaror/'.$article->id, ['decision' => 'approve'])
            ->assertSessionHasErrors(['conference_certificate']);

        $this->assertSame('manual', $article->fresh()->status);
        $this->assertSame('pending', $certificate->fresh()->status);

        $this->actingAs($admin)
            ->post('/moderator/maqola/'.$article->id.'/sertifikat', ['decision' => 'verify'])
            ->assertRedirect();

        $this->assertSame('verified', $certificate->fresh()->status);
        $this->assertSame($admin->id, $certificate->fresh()->verified_by);
        $this->assertNotNull($certificate->fresh()->verified_at);

        $this->assertSame('verified', $certificate->fresh()->status);
        $this->actingAs($admin)
            ->post('/admin/qaror/'.$article->id, ['decision' => 'approve'])
            ->assertSessionHas('ok');
        $this->assertSame('approved', $article->fresh()->status);
    }

    public function test_article_form_contains_type_family_sections_for_conditional_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/yuklash')
            ->assertOk()
            ->assertSee('data-article-family="journal"', false)
            ->assertSee('data-article-family="scopus"', false)
            ->assertSee('data-article-family="conference"', false)
            ->assertSee('conference_certificate', false);
    }

    public function test_dangerous_journal_warning_is_visible_on_article_detail(): void
    {
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $journal = Journal::create([
            'issn' => '3456-7890',
            'name' => 'X xavfli jurnal',
            'field' => 'Iqtisodiyot',
            'tier' => 'X',
            'listed_from' => '2019-01-01',
        ]);
        $article = $this->createApprovedArticle($user, [
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'issn' => $journal->issn,
        ]);

        $this->actingAs($user)
            ->get('/maqola/'.$article->id)
            ->assertOk()
            ->assertSee('Ushbu jurnal xavfli jurnallar ro‘yxatida; maqola 0 ball oladi.');
    }

    public function test_approved_journal_less_scopus_article_is_visible_and_filterable_in_public_base(): void
    {
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $article = $this->createApprovedArticle($user, [
            'title' => 'Tasdiqlangan Scopus maqolasi',
            'type' => 'scopus_q12',
            'journal_name' => 'Scopus xalqaro nashri',
            'field' => 'Iqtisodiyot',
            'journal_id' => null,
        ]);

        $this->get('/baza?q='.$article->title)
            ->assertOk()
            ->assertSee('Tasdiqlangan Scopus maqolasi')
            ->assertSee('Scopus xalqaro nashri')
            ->assertSee('Iqtisodiyot')
            ->assertSee('Daraja A');

        $this->get('/baza?field=Iqtisodiyot&tier=A')
            ->assertOk()
            ->assertSee('Tasdiqlangan Scopus maqolasi');
    }

    private function createApprovedArticle(User $user, array $overrides = []): Article
    {
        return Article::create(array_replace([
            'user_id' => $user->id,
            'title' => 'Maqola '.Str::uuid(),
            'type' => 'scopus_q12',
            'journal_name' => 'Scopus venue',
            'field' => 'Iqtisodiyot',
            'issn' => null,
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/'.Str::uuid(),
            'position' => 'yolgiz',
            'status' => 'approved',
        ], $overrides));
    }

    private function articlePayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Prompt ikki maqolasi '.Str::uuid(),
            'annotation_uz' => str_repeat('O‘zbek tilidagi batafsil ilmiy annotatsiya. ', 3),
            'annotation_ru' => str_repeat('Подробная научная аннотация на русском языке. ', 3),
            'annotation_en' => str_repeat('A detailed scientific abstract written in the English language. ', 3),
            'keywords_uz' => 'ilm, fan, maqola',
            'keywords_ru' => 'наука, статья, исследование',
            'keywords_en' => 'science, article, research',
            'type' => 'journal_local_oak',
            'journal_name' => 'OAK bo‘lmagan jurnal',
            'issn' => null,
            'published_at' => '2025-05-01',
            'url' => 'https://example.uz/'.Str::uuid(),
            'authors' => [['full_name' => 'Muallif F.I.Sh.', 'position' => 'yolgiz', 'is_submitter' => '1']],
            'pdf' => UploadedFile::fake()->create('article.pdf', 16, 'application/pdf'),
        ], $overrides);
    }
}
