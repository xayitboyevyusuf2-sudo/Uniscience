<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class RegistrationTest extends TestCase {
 use RefreshDatabase;
 public function test_registration_stores_first_and_last_name() {
  $this->post('/royxat',['first_name'=>'Elbek','last_name'=>'Karimov','student_id'=>'123','faculty'=>'Iqtisodiyot fakulteti','direction'=>'Iqtisodiyot','course'=>3,'email'=>'e@x.uz','password'=>'password123','password_confirmation'=>'password123','consent'=>1]);
  $this->assertDatabaseHas('users',['email'=>'e@x.uz','name'=>'Elbek Karimov','first_name'=>'Elbek','last_name'=>'Karimov']);
 }
 public function test_home_page_renders_for_guests() { $this->get('/')->assertOk()->assertSee('Ilmiy natijalaringiz bir joyda'); }
}
