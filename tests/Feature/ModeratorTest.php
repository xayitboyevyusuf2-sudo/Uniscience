<?php
namespace Tests\Feature;
use App\Models\{Article,Journal,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ModeratorTest extends TestCase {
 use RefreshDatabase;
 public function test_moderator_pick_approves_and_learns_issn() {
  $j = Journal::create(['issn'=>null,'name'=>'Real jurnal','field'=>'Iqtisodiyot','tier'=>'D','listed_from'=>'2019-01-01']);
  $admin = User::factory()->create(['role'=>'admin','faculty'=>'F']);
  $stu = User::factory()->create(['direction'=>'Iqtisodiyot','faculty'=>'F']);
  $a = Article::create(['user_id'=>$stu->id,'title'=>'T','journal_name'=>'real jurn','issn'=>'3333-4444','published_at'=>'2025-05-01','url'=>'https://x.uz/m','position'=>'yolgiz','status'=>'manual']);
  $this->actingAs($admin)->post("/admin/qaror/{$a->id}",['decision'=>'approve','journal_pick'=>$j->id.' · Real jurnal']);
  $this->assertSame('approved',$a->fresh()->status);
  $this->assertSame('3333-4444',$j->fresh()->issn);
 }
}
