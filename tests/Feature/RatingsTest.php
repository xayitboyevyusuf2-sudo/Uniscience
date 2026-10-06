<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Journal;
use App\Models\Rating;
use App\Models\User;
use App\Services\RatingService;
use App\Services\Scorer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RatingsTest extends TestCase
{
    use RefreshDatabase;

    // TS-13: D(3) x birinchi(0.8) x joriy yil(1.0) x mos soha(1.0) = 2.4 with a rating_items row
    public function test_ts13_score_and_rating_item_are_cached_from_scorer(): void
    {
        $user = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'TS13 jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        $article = Article::create([
            'user_id' => $user->id,
            'title' => 'TS13 maqola',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/ts13',
            'position' => 'birinchi',
            'status' => 'approved',
        ]);

        $rating = RatingService::recalculateUser($user);

        $this->assertSame('2.40', $rating->score);
        $item = DB::table('rating_items')->where('rating_id', $rating->id)->where('article_id', $article->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(1.0, (float) $item->w_soha);
        $this->assertSame(3.0, (float) $item->w_daraja);
        $this->assertSame(0.8, (float) $item->w_muallif);
        $this->assertSame(1.0, (float) $item->w_sana);
        $this->assertSame(2.4, (float) $item->points);
        $this->assertSame(1, (int) $item->counted);
    }

    // TS-14: 7th article of the year is not counted, with the reason visible
    public function test_ts14_seventh_article_is_not_counted_and_reason_shown(): void
    {
        $user = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'TS14 jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        foreach (range(1, 7) as $i) {
            Article::create([
                'user_id' => $user->id,
                'title' => 'TS14 maqola '.$i,
                'type' => 'journal_local_oak',
                'journal_id' => $journal->id,
                'journal_name' => $journal->name,
                'published_at' => now()->toDateString(),
                'url' => 'https://example.uz/ts14-'.$i,
                'position' => 'yolgiz',
                'status' => 'approved',
                'created_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $rating = RatingService::recalculateUser($user);

        $this->assertSame(7, $rating->items()->count());
        $this->assertSame(1, $rating->items()->where('counted', false)->count());
        $this->assertSame('14.40', $rating->fresh()->score);

        $this->actingAs($user)->get('/reyting/mening')
            ->assertOk()
            ->assertSee('Yillik chegaradan oshgan — hisobga olinmadi')
            ->assertSee('14.40');
    }

    // TS-15: group/faculty/university ranks are correct, categories separate, ties share ranks
    public function test_ts15_ranks_by_scope_category_separation_and_ties(): void
    {
        $make = function (string $name, string $category, ?string $group, int $score) {
            $user = User::factory()->create([
                'name' => $name,
                'category' => $category,
                'university' => 'Uni',
                'faculty' => 'Iqtisodiyot',
                'group_name' => $group,
            ]);
            Rating::create([
                'user_id' => $user->id,
                'category' => $category,
                'score' => $score,
                'computed_at' => now(),
            ]);

            return $user;
        };

        $a = $make('Birinchi', 'bakalavr', 'G1', 10);
        $b = $make('Ikkinchi', 'bakalavr', 'G1', 10);
        $c = $make('Uchinchi', 'bakalavr', 'G1', 7);
        $d = $make('To‘rtinchi', 'bakalavr', 'G2', 5);
        $prof = $make('Prof', 'professor', null, 1);

        RatingService::refreshRanks();

        // ties share a rank: 1, 1, 3 in group G1
        $this->assertSame(1, Rating::where('user_id', $a->id)->value('rank_group'));
        $this->assertSame(1, Rating::where('user_id', $b->id)->value('rank_group'));
        $this->assertSame(3, Rating::where('user_id', $c->id)->value('rank_group'));
        // group G2 is ranked separately
        $this->assertSame(1, Rating::where('user_id', $d->id)->value('rank_group'));
        // faculty scope spans both groups
        $this->assertSame(1, Rating::where('user_id', $a->id)->value('rank_faculty'));
        $this->assertSame(3, Rating::where('user_id', $c->id)->value('rank_faculty'));
        $this->assertSame(4, Rating::where('user_id', $d->id)->value('rank_faculty'));
        // categories are ranked separately at university scope
        $this->assertSame(1, Rating::where('user_id', $prof->id)->value('rank_university'));
        $this->assertSame(4, Rating::where('user_id', $d->id)->value('rank_university'));
    }

    public function test_zero_score_users_rank_in_category_but_stay_out_of_top_list(): void
    {
        $zero = User::factory()->create(['name' => 'Nol Ball', 'category' => 'bakalavr', 'university' => 'Uni', 'faculty' => 'Iqtisodiyot', 'group_name' => 'G1']);
        $scored = User::factory()->create(['name' => 'Balli Talaba', 'category' => 'bakalavr', 'university' => 'Uni', 'faculty' => 'Iqtisodiyot', 'group_name' => 'G1']);
        Rating::create(['user_id' => $zero->id, 'category' => 'bakalavr', 'score' => 0, 'computed_at' => now()]);
        Rating::create(['user_id' => $scored->id, 'category' => 'bakalavr', 'score' => 5, 'computed_at' => now()]);
        RatingService::refreshRanks();

        $this->assertSame(2, Rating::where('user_id', $zero->id)->value('rank_university'));

        $response = $this->actingAs($zero)->get('/reyting')->assertOk();
        $response->assertSee('Balli Talaba');
        $response->assertSee('Sizning atrofingiz');
        $response->assertSee('Nol Ball');
    }

    public function test_top50_and_neighborhood_of_five_above_and_below(): void
    {
        $me = null;
        foreach (range(1, 60) as $i) {
            $user = User::factory()->create([
                'name' => 'Ishtirokchi'.$i,
                'category' => 'bakalavr',
                'university' => 'Uni',
                'faculty' => 'Iqtisodiyot',
                'group_name' => 'G1',
            ]);
            Rating::create(['user_id' => $user->id, 'category' => 'bakalavr', 'score' => 100 - $i, 'computed_at' => now()]);
            if ($i === 60) {
                $me = $user;
            }
        }
        RatingService::refreshRanks();

        $response = $this->actingAs($me)->get('/reyting')->assertOk();
        $response->assertSee('Ishtirokchi50');
        $response->assertSee('Ishtirokchi55');
        $response->assertSee('Ishtirokchi59');
        $response->assertSee('Ishtirokchi60');
        $response->assertSee('Sizning atrofingiz');
        $this->assertStringNotContainsString('Ishtirokchi54', strip_tags($response->getContent() ?? ''));
    }

    public function test_monthly_recalculate_command_populates_ratings_and_is_scheduled(): void
    {
        User::factory()->count(3)->create(['category' => 'bakalavr']);

        Artisan::call('ratings:recalculate');

        $this->assertSame(3, Rating::count());
        $output = Artisan::output();
        $this->assertStringContainsString('qayta hisoblandi', $output);

        $schedule = app(Schedule::class);
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command ?? '', 'ratings:recalculate'));
        $this->assertTrue($events->isNotEmpty());
    }

    public function test_article_decision_refreshes_cached_rating(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'Qaror jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        $article = Article::create([
            'user_id' => $owner->id,
            'title' => 'Qaror maqolasi',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/qaror',
            'position' => 'yolgiz',
            'status' => 'manual',
        ]);

        $this->actingAs($admin)->post('/admin/qaror/'.$article->id, ['decision' => 'approve'])->assertRedirect();

        $rating = Rating::where('user_id', $owner->id)->first();
        $this->assertNotNull($rating);
        $this->assertSame(Scorer::for($owner)['total'], (float) $rating->score);
    }

    // FR-33: professor gets own articles plus supervised (oxirgi) articles at 0.6; yearly limit applies to own only
    public function test_professor_supervised_articles_score_at_06_without_yearly_limit(): void
    {
        $professor = User::factory()->create(['category' => 'professor', 'direction' => 'Iqtisodiyot']);
        $student = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'Rahbar jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        foreach (range(1, 7) as $i) {
            $article = Article::create([
                'user_id' => $student->id,
                'title' => 'Rahbarlik maqolasi '.$i,
                'type' => 'journal_local_oak',
                'journal_id' => $journal->id,
                'journal_name' => $journal->name,
                'published_at' => now()->toDateString(),
                'url' => 'https://example.uz/rahbar-'.$i,
                'position' => 'ortadagi',
                'status' => 'approved',
            ]);
            ArticleAuthor::create([
                'article_id' => $article->id,
                'user_id' => $professor->id,
                'full_name' => 'Rahbar F.I.Sh.',
                'position' => 'oxirgi',
                'is_submitter' => false,
                'sort' => 1,
            ]);
        }

        $result = Scorer::forSupervised($professor);

        // each supervised article: 1.0 x 3 x 0.6 x 1.0 = 1.8; all 7 count (limit is own-articles only)
        $this->assertSame(12.6, $result['total']);
        $this->assertTrue(collect($result['rows'])->every(fn ($row) => $row['supervised'] && $row['counted']));
        // Scorer::for is untouched and still reports zero for the professor
        $this->assertSame(0.0, Scorer::for($professor)['total']);
    }

    public function test_csv_export_has_bom_and_uzbek_letters_only_for_admin_and_leadership(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'G‘aniyev O‘tkir', 'category' => 'bakalavr']);
        $student = User::factory()->create(['role' => 'student']);
        Rating::create(['user_id' => $admin->id, 'category' => 'bakalavr', 'score' => 4, 'computed_at' => now()]);
        RatingService::refreshRanks();

        $response = $this->actingAs($admin)->get('/reyting/eksport.csv')->assertOk();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('G‘aniyev O‘tkir', $content);

        $this->actingAs($student)->get('/reyting/eksport.csv')->assertForbidden();
    }

    public function test_pdf_export_returns_pdf_for_admin_and_forbids_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'category' => 'bakalavr']);
        $student = User::factory()->create(['role' => 'student']);
        Rating::create(['user_id' => $admin->id, 'category' => 'bakalavr', 'score' => 4, 'computed_at' => now()]);

        $response = $this->actingAs($admin)->get('/reyting/eksport.pdf')->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $this->actingAs($student)->get('/reyting/eksport.pdf')->assertForbidden();
    }

    public function test_profile_page_shows_ranks_and_matches_scorer(): void
    {
        $user = User::factory()->create(['category' => 'bakalavr', 'direction' => 'Iqtisodiyot', 'university' => 'Uni', 'faculty' => 'Iqtisodiyot', 'group_name' => 'G1']);
        $journal = Journal::create(['name' => 'Profil jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        Article::create([
            'user_id' => $user->id,
            'title' => 'Profil maqolasi',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => now()->toDateString(),
            'url' => 'https://example.uz/profil',
            'position' => 'yolgiz',
            'status' => 'approved',
        ]);

        $rating = RatingService::recalculateUser($user);
        RatingService::refreshRanks();
        $rating->refresh();

        // optional consistency: cached score equals live Scorer total
        $this->assertSame(Scorer::for($user)['total'], (float) $rating->score);

        $this->actingAs($user)->get('/talaba/'.$user->id)
            ->assertOk()
            ->assertSee('O‘rin')
            ->assertSee((string) $rating->rank_university);
    }
}
