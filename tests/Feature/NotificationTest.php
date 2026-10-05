<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\AccountApproved;
use App\Notifications\ArticleStatus;
use App\Notifications\ResetPasswordUz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_sends_uzbek_email()
    {
        Notification::fake();
        $u = User::factory()->create();
        $this->post('/parolni-tiklash', ['email' => $u->email]);
        Notification::assertSentTo($u, ResetPasswordUz::class);
    }

    public function test_upload_sends_status_email()
    {
        Notification::fake();
        Journal::create(['issn' => '1111-2222', 'name' => 'Test jurnal', 'field' => 'Iqtisodiyot', 'tier' => 'D', 'listed_from' => '2019-01-01']);
        $u = User::factory()->create(['direction' => 'Iqtisodiyot']);
        $this->actingAs($u)->post('/yuklash', ['title' => 'T', 'journal_name' => 'Test jurnal', 'issn' => '1111-2222', 'published_at' => '2025-05-01', 'url' => 'https://x.uz/a', 'position' => 'yolgiz']);
        Notification::assertSentTo($u, ArticleStatus::class);
    }

    public function test_article_status_notification_is_saved_to_database()
    {
        $user = User::factory()->create();
        $article = Article::create(['user_id' => $user->id, 'title' => 'Maqola', 'journal_name' => 'Test jurnal', 'issn' => '1111-2222', 'published_at' => '2025-05-01', 'url' => 'https://x.uz/notification', 'position' => 'yolgiz', 'status' => 'approved', 'reason' => 'OAK ro‘yxatida']);

        $user->notify(new ArticleStatus($article));

        $notification = $user->notifications()->firstOrFail();
        $this->assertSame(ArticleStatus::class, $notification->type);
        $this->assertSame([
            'title' => 'Maqolangiz holati: Tasdiqlandi',
            'body' => '«Maqola» maqolangiz holati: Tasdiqlandi. OAK ro‘yxatida',
            'url' => '/maqola/'.$article->id,
            'type' => 'article_status',
        ], $notification->data);
    }

    public function test_account_approved_notification_is_saved_to_database()
    {
        $user = User::factory()->create();

        $user->notify(new AccountApproved);

        $notification = $user->notifications()->firstOrFail();
        $this->assertSame(AccountApproved::class, $notification->type);
        $this->assertSame([
            'title' => 'Hisobingiz tasdiqlandi',
            'body' => 'Administrator hisobingizni tasdiqladi. Endi tizimga kirishingiz mumkin.',
            'url' => '/kirish',
            'type' => 'account_approved',
        ], $notification->data);
    }
}
