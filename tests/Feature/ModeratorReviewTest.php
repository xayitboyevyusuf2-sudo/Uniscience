<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ConferenceCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModeratorReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_multiple_faculties_to_moderator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $moderator = User::factory()->create(['role' => 'student', 'faculty' => 'Old Faculty']);
        User::factory()->create(['faculty' => 'Faculty A']);
        User::factory()->create(['faculty' => 'Faculty B']);

        $this->actingAs($admin)
            ->post('/admin/foydalanuvchi/'.$moderator->id, [
                'role' => 'moderator',
                'faculty_ids' => ['Faculty A', 'Faculty B'],
            ])
            ->assertSessionHas('ok', 'Yangilandi');

        $this->assertDatabaseHas('moderator_faculties', ['user_id' => $moderator->id, 'faculty' => 'Faculty A']);
        $this->assertDatabaseHas('moderator_faculties', ['user_id' => $moderator->id, 'faculty' => 'Faculty B']);
    }

    public function test_moderator_queue_lists_manual_articles_certificates_and_requests_only_for_assigned_faculties(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        DB::table('moderator_faculties')->insert(['user_id' => $moderator->id, 'faculty' => 'Faculty A']);
        $facultyAUser = User::factory()->create(['faculty' => 'Faculty A']);
        $facultyBUser = User::factory()->create(['faculty' => 'Faculty B']);
        $articleA = $this->createManualArticle($facultyAUser, 'Faculty A manual article');
        $articleB = $this->createManualArticle($facultyBUser, 'Faculty B manual article');
        $conferenceArticle = $this->createManualArticle($facultyAUser, 'Faculty A conference article', 'conf_local');
        ConferenceCertificate::create(['article_id' => $conferenceArticle->id, 'path' => 'conference-certificates/pending.pdf']);
        $articleA->journalRequests()->create([
            'user_id' => $facultyAUser->id,
            'journal_name' => 'Unlisted faculty A journal',
            'status' => 'open',
        ]);
        $articleB->journalRequests()->create([
            'user_id' => $facultyBUser->id,
            'journal_name' => 'Unlisted faculty B journal',
            'status' => 'open',
        ]);

        $this->actingAs($moderator)
            ->get('/moderator/navbat')
            ->assertOk()
            ->assertSee('Faculty A manual article')
            ->assertSee('Faculty A conference article')
            ->assertSee('Unlisted faculty A journal')
            ->assertDontSee('Faculty B manual article')
            ->assertDontSee('Unlisted faculty B journal');
    }

    public function test_moderator_cannot_open_article_from_unassigned_faculty(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        DB::table('moderator_faculties')->insert(['user_id' => $moderator->id, 'faculty' => 'Faculty A']);
        $article = $this->createManualArticle(User::factory()->create(['faculty' => 'Faculty B']), 'Private article');

        $this->actingAs($moderator)->get('/moderator/maqola/'.$article->id)->assertForbidden();
        $this->actingAs($moderator)->get('/maqola/'.$article->id.'/pdf')->assertForbidden();
    }

    public function test_article_pdf_is_available_to_owner_and_assigned_moderator(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['faculty' => 'Faculty A']);
        $article = $this->createManualArticle($owner, 'PDF article');
        Storage::disk('local')->put($article->pdf_path, '%PDF-1.4 private test');
        $moderator = User::factory()->create(['role' => 'moderator']);
        DB::table('moderator_faculties')->insert(['user_id' => $moderator->id, 'faculty' => 'Faculty A']);

        $this->actingAs($owner)->get('/maqola/'.$article->id.'/pdf')->assertOk();
        $this->actingAs($moderator)->get('/maqola/'.$article->id.'/pdf')->assertOk();
    }

    public function test_moderator_article_detail_shows_auto_check_and_review_history(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        DB::table('moderator_faculties')->insert(['user_id' => $moderator->id, 'faculty' => 'Faculty A']);
        $owner = User::factory()->create(['faculty' => 'Faculty A']);
        $article = $this->createManualArticle($owner, 'Review detail article');
        DB::table('review_logs')->insert([
            'article_id' => $article->id,
            'user_id' => $moderator->id,
            'decision' => 'manual',
            'note' => 'Old review note',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($moderator)
            ->get('/moderator/maqola/'.$article->id)
            ->assertOk()
            ->assertSee('Review detail article')
            ->assertSee('Jurnal ro‘yxatda topilmadi')
            ->assertSee('Old review note')
            ->assertSee('Tasdiqlangan maqola PDF');
    }

    private function createManualArticle(User $user, string $title, string $type = 'journal_local_oak'): Article
    {
        return Article::create([
            'user_id' => $user->id,
            'title' => $title,
            'type' => $type,
            'journal_name' => 'Unlisted review journal',
            'published_at' => '2025-05-01',
            'url' => 'https://example.uz/'.str_replace(' ', '-', strtolower($title)),
            'pdf_path' => 'articles/review.pdf',
            'position' => 'yolgiz',
            'status' => 'manual',
            'reason' => 'Jurnal topilmadi',
        ]);
    }
}
