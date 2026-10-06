<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\ArticleSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_form_shows_multilingual_metadata_authors_and_required_pdf(): void
    {
        $user = User::factory()->create();
        $mentor = User::factory()->create([
            'category' => 'professor',
            'first_name' => 'Ali',
            'last_name' => 'Valiyev',
            'patronymic' => 'Anvarovich',
        ]);

        $this->actingAs($user)
            ->get('/yuklash')
            ->assertOk()
            ->assertSee('annotation_uz', false)
            ->assertSee('annotation_ru', false)
            ->assertSee('annotation_en', false)
            ->assertSee('keywords_uz', false)
            ->assertSee('Muallif qo‘shish')
            ->assertSee('Valiyev Ali Anvarovich')
            ->assertSee('name="pdf"', false)
            ->assertSee('required', false);
    }

    public function test_ts06_multilingual_submission_creates_author_rows_and_pending_database_notification(): void
    {
        Storage::fake('local');
        $submitter = User::factory()->create([
            'category' => 'bakalavr',
            'first_name' => 'Aziza',
            'last_name' => 'Karimova',
            'patronymic' => 'Anvarovna',
        ]);
        $mentor = User::factory()->create([
            'category' => 'professor',
            'first_name' => 'Ali',
            'last_name' => 'Valiyev',
            'patronymic' => 'Anvarovich',
        ]);

        $response = $this->actingAs($submitter)->post('/yuklash', $this->articlePayload([
            'authors' => [
                ['full_name' => 'Aziza Karimova Anvarovna', 'position' => 'birinchi', 'is_submitter' => '1'],
                ['full_name' => 'Ali Valiyev Anvarovich', 'position' => 'oxirgi', 'user_id' => $mentor->id],
            ],
        ]));

        $article = Article::where('user_id', $submitter->id)->firstOrFail();
        $response->assertRedirect('/maqola/'.$article->id);
        $this->assertSame('manual', $article->status);
        $this->assertSame('journal_local_oak', $article->type);
        $this->assertSame('10.1000/example', $article->doi);
        $this->assertSame('Annotatsiya o‘zbek tilida yetarlicha uzun matn hisoblanadi va maqola mazmunini to‘liq bayon qiladi.', $article->abstract);
        $this->assertSame('Русская аннотация содержит достаточно подробное описание научной статьи и результатов исследования.', $article->annotation_ru);
        $this->assertSame('The English abstract contains enough detail to describe this scholarly article and its research results clearly.', $article->annotation_en);
        $this->assertSame('ilm, fan, tadqiqot', $article->keywords_uz);
        $this->assertSame('наука, исследование, статья', $article->keywords_ru);
        $this->assertSame('science, research, article', $article->keywords_en);
        $this->assertSame('birinchi', $article->position);
        $this->assertSame('Ali Valiyev Anvarovich', $article->coauthors);
        $this->assertSame('10.1000/example', $article->doi);
        Storage::disk('local')->assertExists($article->pdf_path);

        $authors = ArticleAuthor::where('article_id', $article->id)->orderBy('sort')->get();
        $this->assertCount(2, $authors);
        $this->assertSame($submitter->id, $authors[0]->user_id);
        $this->assertTrue($authors[0]->is_submitter);
        $this->assertSame($mentor->id, $authors[1]->user_id);
        $this->assertFalse($authors[1]->is_submitter);

        $this->assertSame(['database'], (new ArticleSubmitted($article))->via($submitter));
        $notification = $submitter->notifications()->firstOrFail();
        $this->assertSame(ArticleSubmitted::class, $notification->type);
        $this->assertSame('Maqolangiz qabul qilindi: Ko‘rib chiqilmoqda', $notification->data['title']);
        $this->assertSame('/maqola/'.$article->id, $notification->data['url']);
    }

    public function test_two_annotations_are_rejected(): void
    {
        $user = User::factory()->create();
        $payload = $this->articlePayload();
        unset($payload['annotation_en']);

        $this->actingAs($user)->post('/yuklash', $payload)
            ->assertSessionHasErrors('annotation_en');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_submission_requires_exactly_one_submitter(): void
    {
        $user = User::factory()->create();
        $payload = $this->articlePayload();
        $payload['authors'][0]['is_submitter'] = '0';

        $this->actingAs($user)->post('/yuklash', $payload)
            ->assertSessionHasErrors('authors');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_annotation_shorter_than_fifty_characters_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/yuklash', $this->articlePayload(['annotation_uz' => 'Juda qisqa matn']))
            ->assertSessionHasErrors('annotation_uz');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_fewer_than_three_keywords_in_any_language_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/yuklash', $this->articlePayload(['keywords_ru' => 'kalit, so‘z']))
            ->assertSessionHasErrors('keywords_ru');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_pdf_is_required_and_invalid_mime_is_rejected(): void
    {
        $user = User::factory()->create();
        $payload = $this->articlePayload();
        unset($payload['pdf']);

        $this->actingAs($user)->post('/yuklash', $payload)
            ->assertSessionHasErrors('pdf');

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'pdf' => UploadedFile::fake()->create('malicious.pdf', 8, 'text/plain'),
        ]))->assertSessionHasErrors('pdf');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_duplicate_normalized_title_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/yuklash', $this->articlePayload());

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'title' => 'ilmiy-tadqiqot natijalari!',
            'doi' => null,
            'url' => 'https://example.uz/articles/second',
        ]))->assertSessionHasErrors('title');

        $this->assertDatabaseCount('articles', 1);
    }

    public function test_duplicate_doi_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/yuklash', $this->articlePayload());

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'title' => 'Boshqa maqola nomi',
            'doi' => '10.1000/EXAMPLE',
            'url' => 'https://example.uz/articles/second',
        ]))->assertSessionHasErrors('doi');

        $this->assertDatabaseCount('articles', 1);
    }

    public function test_duplicate_url_with_same_issn_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/yuklash', $this->articlePayload());

        $this->actingAs($user)->post('/yuklash', $this->articlePayload([
            'title' => 'Boshqa maqola nomi',
            'doi' => null,
        ]))->assertSessionHasErrors('url');

        $this->assertDatabaseCount('articles', 1);
    }

    public function test_baza_search_includes_new_multilingual_content_and_legacy_article_detail_uses_abstract(): void
    {
        $user = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $journal = Journal::create([
            'issn' => '2222-3333',
            'name' => 'OAK jurnal',
            'field' => 'Iqtisodiyot',
            'tier' => 'B',
            'listed_from' => '2019-01-01',
        ]);
        $article = Article::create([
            'user_id' => $user->id,
            'journal_id' => $journal->id,
            'title' => 'Legacy maqola',
            'journal_name' => $journal->name,
            'issn' => $journal->issn,
            'published_at' => '2025-05-01',
            'url' => 'https://example.uz/legacy',
            'position' => 'yolgiz',
            'status' => 'approved',
            'abstract' => 'Eski annotatsiya mazmuni',
            'annotation_ru' => 'RU_NEW_ANNOTATION',
            'annotation_en' => 'EN_NEW_ANNOTATION',
            'keywords_uz' => 'UZ_NEW_KEYWORD, fan, ilm',
            'keywords_ru' => 'RU_NEW_KEYWORD, наука, статья',
            'keywords_en' => 'EN_NEW_KEYWORD, science, research',
        ]);

        $this->get('/baza?q=RU_NEW_ANNOTATION')->assertSee('Legacy maqola');
        $this->get('/baza?q=EN_NEW_KEYWORD')->assertSee('Legacy maqola');
        $this->actingAs($user)
            ->get('/maqola/'.$article->id)
            ->assertSee('Eski annotatsiya mazmuni')
            ->assertSee('RU_NEW_ANNOTATION')
            ->assertSee('EN_NEW_KEYWORD');
    }

    private function articlePayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Ilmiy tadqiqot natijalari',
            'annotation_uz' => 'Annotatsiya o‘zbek tilida yetarlicha uzun matn hisoblanadi va maqola mazmunini to‘liq bayon qiladi.',
            'annotation_ru' => 'Русская аннотация содержит достаточно подробное описание научной статьи и результатов исследования.',
            'annotation_en' => 'The English abstract contains enough detail to describe this scholarly article and its research results clearly.',
            'keywords_uz' => 'ilm, fan, tadqiqot',
            'keywords_ru' => 'наука, исследование, статья',
            'keywords_en' => 'science, research, article',
            'type' => 'journal_local_oak',
            'published_at' => '2025-05-01',
            'journal_name' => 'Ro‘yxatda bo‘lmagan jurnal',
            'issn' => '1111-2222',
            'url' => 'https://example.uz/articles/first',
            'doi' => '10.1000/example',
            'authors' => [
                ['full_name' => 'Aziza Karimova Anvarovna', 'position' => 'yolgiz', 'is_submitter' => '1'],
            ],
            'pdf' => UploadedFile::fake()->create('paper.pdf', 12, 'application/pdf'),
        ], $overrides);
    }
}
