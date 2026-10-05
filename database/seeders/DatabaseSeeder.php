<?php

namespace Database\Seeders;

use App\Models\Journal;
use App\Models\User;
use App\Services\JournalImporter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('data/oak_import.csv');
        if (Journal::count() === 0 && is_file($csv)) {
            JournalImporter::fromFile($csv);
        } // real OAK list, ISSN-free
        // Admin is created ONLY if ADMIN_EMAIL and ADMIN_PASSWORD are set (no default password anywhere).
        if (config('uniscience.admin_email') && config('uniscience.admin_password')) {
            User::updateOrCreate(['email' => config('uniscience.admin_email')], ['name' => 'Admin', 'first_name' => 'Admin', 'email_verified_at' => now(), 'password' => config('uniscience.admin_password'), 'role' => 'admin', 'faculty' => 'Iqtisodiyot fakulteti']);
        }
    }
}
