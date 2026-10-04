<?php
namespace Tests\Feature;
use App\Models\{Article,Journal,User};
use App\Services\{Scorer,Verifier};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class VerificationTest extends TestCase {
 use RefreshDatabase;
 private function go(string $pub, ?string $from='2019-01-01', ?string $to=null, string $name='Test jurnal', bool $journal=true) {
  if ($journal) Journal::create(['issn'=>'1111-2222','name'=>'Test jurnal','field'=>'Iqtisodiyot','tier'=>'D','listed_from'=>$from,'listed_to'=>$to]);
  $u = User::factory()->create(['direction'=>'Iqtisodiyot']);
  $a = Article::create(['user_id'=>$u->id,'title'=>'T','journal_name'=>$name,'issn'=>'1111-2222','published_at'=>$pub,'url'=>'https://x.uz/'.uniqid(),'position'=>'yolgiz','status'=>'pending']);
  return [$u,$a,Verifier::run($a)];
 }
 public function test_ts02_listed_in_range_is_approved() { $this->assertSame('approved', $this->go('2025-05-01')[2][0]); }
 public function test_unknown_journal_goes_to_moderator() { [, , $r] = $this->go('2025-05-01', journal:false); $this->assertSame('manual',$r[0]); $this->assertStringContainsString('topilmadi',$r[1]); }
 public function test_name_match_without_issn_is_approved() {
  Journal::create(['issn'=>null,'name'=>'Real jurnal','field'=>'Iqtisodiyot','tier'=>'D','listed_from'=>'2019-01-01']);
  $u = User::factory()->create(['direction'=>'Iqtisodiyot']);
  $a = Article::create(['user_id'=>$u->id,'title'=>'T','journal_name'=>'REAL jurnal!','issn'=>null,'published_at'=>'2025-05-01','url'=>'https://x.uz/n','position'=>'yolgiz','status'=>'pending']);
  $this->assertSame('approved', Verifier::run($a)[0]);
 }
 public function test_ts04_delisted_later_still_approved() { $this->assertSame('approved', $this->go('2023-05-01','2019-01-01','2024-06-30')[2][0]); }
 public function test_ts05_listed_after_publication_is_rejected() { $this->assertSame('rejected', $this->go('2023-05-01','2025-01-01')[2][0]); }
 public function test_ts06_name_mismatch_goes_to_manual() { $this->assertSame('manual', $this->go('2025-05-01',name:'Boshqa nom')[2][0]); }
 public function test_ts08_score_is_three() {
  [$u,$a,[$st,,$j]] = $this->go(now()->toDateString());
  $a->update(['status'=>$st,'journal_id'=>$j->id]);
  $this->assertEquals(3.0, Scorer::for($u)['total']);
 }
}
