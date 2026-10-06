<?php

namespace Tests\Feature;

use App\Integrations\Ministry\MinistryExportAdapter;
use App\Models\Article;
use App\Models\Journal;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadershipDashboardTest extends TestCase
{
    use RefreshDatabase;

    // TS-20: with known data, dashboard numbers match database counts
    public function test_ts20_dashboard_counts_match_database(): void
    {
        $leader = User::factory()->create(['role' => 'rahbariyat']);
        $facultyA = 'Iqtisodiyot';
        $facultyB = 'Tarix';
        $journal = Journal::create(['name' => 'Dash jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'C', 'listed_from' => '2019-01-01']);

        $this->createArticle($facultyA, $journal, '2026-03-10 12:00:00');
        $this->createArticle($facultyA, $journal, '2026-03-15 12:00:00');
        $this->createArticle($facultyA, $journal, '2026-05-10 12:00:00');
        $this->createArticle($facultyB, $journal, '2026-03-20 12:00:00');

        $response = $this->actingAs($leader)->get('/rahbariyat?period=yil&year=2026');
        $response->assertOk();

        $expectedUploaded = DB::table('articles')->join('users', 'users.id', '=', 'articles.user_id')
            ->where('users.faculty', $facultyA)->whereYear('articles.created_at', 2026)->count();
        $expectedApproved = DB::table('articles')->join('users', 'users.id', '=', 'articles.user_id')
            ->where('users.faculty', $facultyA)->where('articles.status', 'approved')->whereYear('articles.decided_at', 2026)->count();

        $response->assertSee('>'.$expectedUploaded.'<', false);
        $response->assertSee('>'.$expectedApproved.'<', false);
        $response->assertSee('Fakultetlar kesimida');
        $response->assertSee('Yillik dinamika');
    }

    public function test_period_and_faculty_filters_change_results(): void
    {
        $leader = User::factory()->create(['role' => 'rahbariyat']);
        $journal = Journal::create(['name' => 'Filtr jurnali', 'field' => 'Tarix', 'tier' => 'B', 'listed_from' => '2019-01-01']);
        $this->createArticle('Iqtisodiyot', $journal, '2026-03-10 12:00:00');
        $this->createArticle('Tarix', $journal, '2026-06-10 12:00:00');

        $this->actingAs($leader)->get('/rahbariyat?period=oy&month=3&year=2026')
            ->assertOk()
            ->assertSee('Iqtisodiyot')
            ->assertDontSee('>Tarix</td><td>1<', false);

        $this->actingAs($leader)->get('/rahbariyat?period=yil&year=2026&faculty='.urlencode('Tarix'))
            ->assertOk()
            ->assertSee('Tarix');
    }

    public function test_students_and_moderators_get_403_on_leadership_routes(): void
    {
        foreach (['student', 'moderator'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/rahbariyat')->assertForbidden();
            $this->actingAs($user)->get('/rahbariyat/eksport.csv')->assertForbidden();
            $this->actingAs($user)->get('/rahbariyat/eksport.pdf')->assertForbidden();
        }
    }

    public function test_csv_export_has_bom_uzbek_letters_and_correct_values(): void
    {
        $leader = User::factory()->create(['role' => 'rahbariyat', 'faculty' => 'Iqtisodiyot']);
        $journal = Journal::create(['name' => 'CSV jurnali', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        $this->createArticle('Iqtisodiyot', $journal, '2026-03-10 12:00:00');
        $topUser = User::factory()->create(['name' => 'G‘ulom O‘tkir', 'category' => 'bakalavr', 'faculty' => 'Iqtisodiyot']);
        Rating::create(['user_id' => $topUser->id, 'category' => 'bakalavr', 'score' => 9, 'computed_at' => now()]);

        $response = $this->actingAs($leader)->get('/rahbariyat/eksport.csv?year=2026')->assertOk();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('G‘ulom O‘tkir', $content);
        $this->assertStringContainsString('Iqtisodiyot,1,1', $content);
        $this->assertStringContainsString('Fakultet o‘rni', $content);
    }

    public function test_pdf_export_returns_pdf(): void
    {
        $leader = User::factory()->create(['role' => 'rahbariyat']);

        $response = $this->actingAs($leader)->get('/rahbariyat/eksport.pdf?year=2026')->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_ministry_placeholder_page_and_button_are_visible(): void
    {
        $leader = User::factory()->create(['role' => 'rahbariyat']);

        $this->actingAs($leader)->get('/rahbariyat')->assertOk()->assertSee('Vazirlik paneli (2-bosqich)');
        $this->actingAs($leader)->get('/vazirlik')->assertOk()->assertSee('2-bosqichda');
        $this->assertTrue(interface_exists(MinistryExportAdapter::class));
    }

    private function createArticle(string $faculty, Journal $journal, string $decidedAt): Article
    {
        $owner = User::factory()->create(['faculty' => $faculty, 'direction' => $journal->field]);

        return Article::create([
            'user_id' => $owner->id,
            'title' => 'Dash maqola '.uniqid(),
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'published_at' => '2026-01-15',
            'url' => 'https://example.uz/'.uniqid(),
            'position' => 'yolgiz',
            'status' => 'approved',
            'decided_at' => $decidedAt,
            'created_at' => $decidedAt,
            'updated_at' => $decidedAt,
        ]);
    }
}
