<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ProfileTest extends TestCase {
 use RefreshDatabase;
 public function test_student_can_update_profile_and_upload_photo() {
  Storage::fake();
  $u = User::factory()->create(['direction'=>'Iqtisodiyot','faculty'=>'F','course'=>3]);
  $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
  $this->actingAs($u)->post('/profil',['first_name'=>'Ali','last_name'=>'Valiyev','course'=>4,'bio'=>'Salom dunyo','photo'=>UploadedFile::fake()->createWithContent('a.png',$png)]);
  $u->refresh();
  $this->assertSame('Ali Valiyev',$u->name); $this->assertNotNull($u->photo_path); Storage::assertExists($u->photo_path);
  $this->actingAs($u)->get('/foto/'.$u->id)->assertOk();
  $this->actingAs($u)->get('/talaba/'.$u->id)->assertOk()->assertSee('Salom dunyo');
 }
 public function test_profile_pages_require_login() { $this->get('/profil')->assertRedirect('/kirish'); $this->get('/talaba/1')->assertRedirect('/kirish'); }
}
