<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Journal;
use App\Models\User;
use App\Services\Verifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_moderator_cannot_access_journal_crud(): void
    {
        $journal = $this->createJournal(['name' => 'Himoya jurnali']);

        foreach (['student', 'moderator'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/admin/jurnallar')->assertForbidden();
            $this->actingAs($user)->get('/admin/jurnallar/yangi')->assertForbidden();
            $this->actingAs($user)->post('/admin/jurnallar', [])->assertForbidden();
            $this->actingAs($user)->get('/admin/jurnallar/'.$journal->id.'/tahrir')->assertForbidden();
            $this->actingAs($user)->post('/admin/jurnallar/'.$journal->id, [])->assertForbidden();
            $this->actingAs($user)->post('/admin/jurnallar/'.$journal->id.'/chiqarish')->assertForbidden();
        }

        $this->assertDatabaseHas('journals', ['id' => $journal->id, 'name' => 'Himoya jurnali']);
    }

    public function test_admin_can_create_journal_with_multi_field_and_audit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/jurnallar', [
            'issn' => '1234-5678',
            'name' => 'Yangi ilmiy jurnal',
            'field' => ['Iqtisodiyot', 'Tarix'],
            'tier' => 'C',
            'listed_from' => '2020-01-01',
            'country' => 'UZ',
            'publisher' => 'Nashriyot',
            'source' => 'xalqaro OAK',
        ])->assertRedirect('/admin/jurnallar');

        $journal = Journal::where('issn', '1234-5678')->firstOrFail();
        $this->assertSame('Iqtisodiyot; Tarix', $journal->field);
        $this->assertSame('xalqaro OAK', $journal->source);

        $entry = DB::table('audit_logs')->where('action', 'journal.created')->first();
        $this->assertNotNull($entry);
        $this->assertNull(json_decode($entry->old, true));
        $this->assertSame('Yangi ilmiy jurnal', json_decode($entry->new, true)['name']);
        $this->assertSame('Iqtisodiyot; Tarix', json_decode($entry->new, true)['field']);
    }

    public function test_journal_edit_records_old_and_new_values_in_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = $this->createJournal(['name' => 'Eski nom', 'tier' => 'B', 'field' => 'Tarix']);

        $this->actingAs($admin)->post('/admin/jurnallar/'.$journal->id, [
            'name' => 'Yangi nom',
            'field' => ['Tarix', 'Filologiya'],
            'tier' => 'A',
            'listed_from' => '2019-01-01',
        ])->assertRedirect('/admin/jurnallar');

        $journal = $journal->fresh();
        $this->assertSame('Yangi nom', $journal->name);
        $this->assertSame('Tarix; Filologiya', $journal->field);
        $this->assertSame('A', $journal->tier);

        $entry = DB::table('audit_logs')->where('action', 'journal.updated')->latest('id')->first();
        $this->assertNotNull($entry);
        $old = json_decode($entry->old, true);
        $new = json_decode($entry->new, true);
        $this->assertSame('Eski nom', $old['name']);
        $this->assertSame('B', $old['tier']);
        $this->assertSame('Yangi nom', $new['name']);
        $this->assertSame('A', $new['tier']);
    }

    public function test_invalid_issn_and_missing_tier_x_warning_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/jurnallar', [
            'issn' => '12345678',
            'name' => 'Noto‘g‘ri ISSN jurnal',
            'field' => ['Tarix'],
            'tier' => 'B',
            'listed_from' => '2020-01-01',
        ])->assertSessionHasErrors('issn');

        $this->actingAs($admin)->post('/admin/jurnallar', [
            'name' => 'Xavfli jurnal',
            'field' => ['Tarix'],
            'tier' => 'X',
            'listed_from' => '2020-01-01',
        ])->assertSessionHasErrors('warning_text');

        $this->assertDatabaseMissing('journals', ['name' => 'Xavfli jurnal']);
    }

    public function test_delist_sets_listed_to_new_articles_rejected_by_date_rule_and_approved_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $journal = $this->createJournal(['issn' => '1111-2222', 'name' => 'Chiqariladigan jurnal', 'listed_from' => '2020-01-01']);
        $approved = Article::create([
            'user_id' => $owner->id,
            'title' => 'Ilgari tasdiqlangan maqola',
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'issn' => $journal->issn,
            'published_at' => '2021-05-01',
            'url' => 'https://example.uz/ilgari-tasdiqlangan',
            'position' => 'yolgiz',
            'status' => 'approved',
            'reason' => 'Tasdiqlandi',
        ]);

        $this->actingAs($admin)
            ->post('/admin/jurnallar/'.$journal->id.'/chiqarish')
            ->assertRedirect();

        $this->assertSame(now()->toDateString(), $journal->fresh()->listed_to->toDateString());

        $approvedAfter = $approved->fresh();
        $this->assertSame('approved', $approvedAfter->status);
        $this->assertSame($journal->id, $approvedAfter->journal_id);
        $this->assertSame('Tasdiqlandi', $approvedAfter->reason);

        $future = new Article([
            'user_id' => $owner->id,
            'title' => 'Keyingi maqola',
            'type' => 'journal_local_oak',
            'journal_name' => $journal->name,
            'issn' => $journal->issn,
            'published_at' => now()->addDay()->toDateString(),
            'url' => 'https://example.uz/keyingi',
            'position' => 'yolgiz',
        ]);
        [$status, $reason] = Verifier::run($future);
        $this->assertSame('rejected', $status);
        $this->assertStringContainsString('ro‘yxatda bo‘lmagan', $reason);

        $entry = DB::table('audit_logs')->where('action', 'journal.delisted')->first();
        $this->assertNotNull($entry);
        $this->assertNull(json_decode($entry->old, true)['listed_to']);
        $this->assertSame(now()->toDateString(), substr((string) json_decode($entry->new, true)['listed_to'], 0, 10));
    }

    public function test_json_import_reports_imported_and_skipped_rows_with_reasons_and_never_deletes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = $this->createJournal(['name' => 'Mavjud jurnal']);
        $payload = [
            ['issn' => '2222-3333', 'name' => 'JSON jurnal', 'field' => 'Tarix', 'tier' => 'C', 'listed_from' => '2018-01-01', 'country' => 'UZ', 'publisher' => 'Pub', 'source' => 'Scopus/ESCI/WoS'],
            ['name' => 'Ikkinchi jurnal', 'field' => 'Biologiya', 'tier' => 'D', 'listed_from' => '2019-01-01'],
            ['name' => 'Buzilgan tier', 'field' => 'Tarix', 'tier' => 'ZZ', 'listed_from' => '2020-01-01'],
            ['field' => 'Tarix', 'tier' => 'A', 'listed_from' => '2020-01-01'],
        ];

        $response = $this->actingAs($admin)->post('/admin/jurnallar/import-json', [
            'json' => UploadedFile::fake()->createWithContent('jurnallar.json', json_encode($payload, JSON_UNESCAPED_UNICODE)),
        ]);

        $response->assertRedirect()
            ->assertSessionHas('ok', '2 ta yozuv import qilindi, 2 ta o‘tkazib yuborildi.')
            ->assertSessionHas('import_errors', fn ($errors) => count($errors) === 2
                && str_contains($errors[0], '3-qator')
                && str_contains($errors[1], '4-qator'));

        $this->assertDatabaseHas('journals', ['issn' => '2222-3333', 'source' => 'Scopus/ESCI/WoS']);
        $this->assertDatabaseHas('journals', ['name' => 'Ikkinchi jurnal', 'source' => 'mahalliy OAK']);
        $this->assertDatabaseMissing('journals', ['name' => 'Buzilgan tier']);
        $this->assertDatabaseHas('journals', ['id' => $existing->id, 'name' => 'Mavjud jurnal']);
    }

    public function test_tier_x_warning_is_shown_via_api_upload_form_and_article_page(): void
    {
        $user = User::factory()->create();
        $journal = $this->createJournal([
            'issn' => '9999-0000',
            'name' => 'Xavfli maxsus jurnal',
            'tier' => 'X',
            'warning_text' => 'Maxsus ogohlantirish matni',
        ]);
        $default = $this->createJournal(['issn' => '9999-0001', 'name' => 'Xavfli standart jurnal', 'tier' => 'X']);

        $this->actingAs($user)
            ->getJson('/api/jurnallar/qidiruv?q=Xavfli maxsus')
            ->assertOk()
            ->assertJsonPath('0.warning_text', 'Maxsus ogohlantirish matni');

        $this->actingAs($user)
            ->get('/yuklash')
            ->assertOk()
            ->assertSee(Journal::TIER_X_WARNING);

        $custom = $this->createArticle($user, $journal, 'Maxsus ogohlantirish maqolasi');
        $this->actingAs($user)->get('/maqola/'.$custom->id)->assertOk()->assertSee('Maxsus ogohlantirish matni');

        $fallback = $this->createArticle($user, $default, 'Standart ogohlantirish maqolasi');
        $this->actingAs($user)->get('/maqola/'.$fallback->id)->assertOk()->assertSee(Journal::TIER_X_WARNING);
    }

    public function test_journal_delete_route_does_not_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = $this->createJournal(['name' => 'O‘chirilmas jurnal']);

        $this->actingAs($admin)->delete('/admin/jurnallar/'.$journal->id)->assertMethodNotAllowed();

        $this->assertDatabaseHas('journals', ['id' => $journal->id]);
    }

    public function test_authenticated_users_get_read_only_journal_directory(): void
    {
        $user = User::factory()->create();
        $this->createJournal([
            'name' => 'Ochiq katalog jurnali',
            'field' => 'Tarix',
            'tier' => 'B',
            'listed_from' => '2020-01-01',
            'listed_to' => '2022-01-01',
        ]);

        $this->actingAs($user)
            ->get('/jurnallar')
            ->assertOk()
            ->assertSee('Ochiq katalog jurnali')
            ->assertSee('Tarix')
            ->assertSee('2020-01-01')
            ->assertSee('2022-01-01')
            ->assertDontSee('/admin/jurnallar');
    }

    public function test_guests_are_redirected_from_journal_directory(): void
    {
        $this->get('/jurnallar')->assertRedirect('/kirish');
    }

    public function test_admin_journal_index_search_and_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createJournal(['name' => 'Filtr alpha jurnali', 'issn' => '1000-0001', 'field' => 'Iqtisodiyot; Tarix', 'tier' => 'C', 'source' => 'xalqaro OAK']);
        $this->createJournal(['name' => 'Filtr beta jurnali', 'issn' => '1000-0002', 'field' => 'Biologiya', 'tier' => 'D', 'source' => 'mahalliy OAK', 'listed_to' => '2023-01-01']);

        $this->actingAs($admin)->get('/admin/jurnallar?q=alpha')->assertOk()
            ->assertSee('Filtr alpha jurnali')->assertDontSee('Filtr beta jurnali');
        $this->actingAs($admin)->get('/admin/jurnallar?q=1000-0002')->assertOk()
            ->assertSee('Filtr beta jurnali')->assertDontSee('Filtr alpha jurnali');
        $this->actingAs($admin)->get('/admin/jurnallar?tier=C')->assertOk()
            ->assertSee('Filtr alpha jurnali')->assertDontSee('Filtr beta jurnali');
        $this->actingAs($admin)->get('/admin/jurnallar?source='.urlencode('mahalliy OAK'))->assertOk()
            ->assertSee('Filtr beta jurnali')->assertDontSee('Filtr alpha jurnali');
        $this->actingAs($admin)->get('/admin/jurnallar?holat=chiqarilgan')->assertOk()
            ->assertSee('Filtr beta jurnali')->assertDontSee('Filtr alpha jurnali');
        $this->actingAs($admin)->get('/admin/jurnallar?holat=royxatda')->assertOk()
            ->assertSee('Filtr alpha jurnali')->assertDontSee('Filtr beta jurnali');
        $this->actingAs($admin)->get('/admin/jurnallar?field='.urlencode('Iqtisodiyot'))->assertOk()
            ->assertSee('Filtr alpha jurnali')->assertDontSee('Filtr beta jurnali');
    }

    public function test_existing_csv_import_form_still_works(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = "issn,name,field,tier,listed_from,listed_to,country,publisher\n7777-8888,CSV jurnal,Tarix,D,2015-01-01,,UZ,CSV Nashriyot\n";

        $this->actingAs($admin)->post('/admin/import', [
            'csv' => UploadedFile::fake()->createWithContent('oak.csv', $csv),
        ])->assertRedirect();

        $this->assertDatabaseHas('journals', ['issn' => '7777-8888', 'name' => 'CSV jurnal', 'source' => 'mahalliy OAK']);
    }

    private function createJournal(array $overrides = []): Journal
    {
        return Journal::create(array_merge([
            'name' => 'Test jurnali '.fake()->unique()->numberBetween(1, 99999),
            'field' => 'Tarix',
            'tier' => 'B',
            'listed_from' => '2019-01-01',
        ], $overrides));
    }

    private function createArticle(User $user, Journal $journal, string $title): Article
    {
        return Article::create([
            'user_id' => $user->id,
            'title' => $title,
            'type' => 'journal_local_oak',
            'journal_id' => $journal->id,
            'journal_name' => $journal->name,
            'issn' => $journal->issn,
            'published_at' => '2021-05-01',
            'url' => 'https://example.uz/'.str_replace(' ', '-', mb_strtolower($title)),
            'position' => 'yolgiz',
            'status' => 'approved',
            'reason' => 'Tasdiqlandi',
        ]);
    }
}
