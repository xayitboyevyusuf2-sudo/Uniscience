<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_routes_return_200_or_redirect_to_login_by_role(): void
    {
        $student = User::factory()->create(['role' => 'student', 'category' => 'bakalavr']);
        $admin = User::factory()->create(['role' => 'admin']);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $rahbariyat = User::factory()->create(['role' => 'rahbariyat']);
        $mentor = User::factory()->create(['role' => 'student', 'category' => 'professor', 'approval_status' => 'approved']);
        Journal::create(['name' => 'Sitemap jurnali', 'field' => 'Tarix', 'tier' => 'B', 'listed_from' => '2019-01-01']);

        $this->get('/')->assertOk();
        $this->get('/royhat')->assertRedirect('/royxat');
        $this->get('/royxat')->assertOk();
        $this->get('/parolni-tiklash')->assertOk();
        $this->get('/baza')->assertOk();
        $this->get('/reyting')->assertOk();

        $this->actingAs($student)->get('/yoriqnoma')->assertOk();
        $this->actingAs($student)->get('/yangiliklar')->assertOk();
        $this->actingAs($student)->get('/matching')->assertOk();
        $this->actingAs($student)->get('/profil')->assertOk();
        $this->actingAs($student)->get('/yuklash')->assertOk();
        $this->actingAs($student)->get('/jurnallar')->assertOk();

        $this->actingAs($moderator)->get('/moderator/navbat')->assertOk();
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($rahbariyat)->get('/rahbariyat')->assertOk();
        $this->actingAs($mentor)->get('/matching/slotlarim')->assertOk();
    }

    public function test_guests_are_redirected_to_login_for_member_pages(): void
    {
        foreach (['/yoriqnoma', '/yangiliklar', '/matching', '/profil', '/yuklash', '/moderator/navbat', '/rahbariyat', '/admin'] as $path) {
            $this->get($path)->assertRedirect();
        }
    }

    public function test_pages_render_200_with_and_without_logo_file(): void
    {
        $this->get('/')->assertOk();

        $dir = public_path('images');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir.'/logo.png', 'fake');
        try {
            $this->get('/')->assertOk()->assertSee('/images/logo.png');
        } finally {
            unlink($dir.'/logo.png');
        }
    }
}
