<?php
namespace Tests\Feature;
use App\Models\{Journal,User};
use App\Notifications\{ArticleStatus,ResetPasswordUz};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
class NotificationTest extends TestCase {
 use RefreshDatabase;
 public function test_reset_link_sends_uzbek_email() {
  Notification::fake(); $u = User::factory()->create();
  $this->post('/parolni-tiklash',['email'=>$u->email]);
  Notification::assertSentTo($u, ResetPasswordUz::class);
 }
 public function test_upload_sends_status_email() {
  Notification::fake();
  Journal::create(['issn'=>'1111-2222','name'=>'Test jurnal','field'=>'Iqtisodiyot','tier'=>'D','listed_from'=>'2019-01-01']);
  $u = User::factory()->create(['direction'=>'Iqtisodiyot']);
  $this->actingAs($u)->post('/yuklash',['title'=>'T','journal_name'=>'Test jurnal','issn'=>'1111-2222','published_at'=>'2025-05-01','url'=>'https://x.uz/a','position'=>'yolgiz']);
  Notification::assertSentTo($u, ArticleStatus::class);
 }
}
