<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('patronymic', 60)->nullable();
            $table->string('username', 190)->nullable()->unique();
            $table->string('category', 20)->nullable();
            $table->string('university', 190)->nullable();
            $table->string('group_name', 40)->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('bachelor_university', 190)->nullable();
            $table->string('academic_degree', 120)->nullable();
            $table->string('position_title', 120)->nullable();
            $table->string('department', 190)->nullable();
            $table->string('hemis_id', 64)->nullable()->unique();
            $table->string('auth_provider', 20)->default('local');
            $table->string('approval_status', 20)->default('approved');
        });

        $seenUsernames = [];

        DB::table('users')->select('id', 'role', 'first_name', 'last_name')->orderBy('id')->chunkById(500, function ($users) use (&$seenUsernames): void {
            foreach ($users as $user) {
                $updates = [];

                if ($user->role === 'student') {
                    $updates['category'] = 'bakalavr';
                }

                if ($user->first_name !== null && $user->last_name !== null) {
                    $username = trim($user->last_name.' '.$user->first_name);

                    if ($username !== '') {
                        $key = Str::lower($username);

                        if (! isset($seenUsernames[$key])) {
                            $seenUsernames[$key] = true;
                            $updates['username'] = $username;
                        }
                    }
                }

                if ($updates !== []) {
                    DB::table('users')->where('id', $user->id)->update($updates);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropUnique(['hemis_id']);
            $table->dropColumn([
                'patronymic', 'username', 'category', 'university', 'group_name', 'gpa',
                'birth_date', 'bachelor_university', 'academic_degree', 'position_title',
                'department', 'hemis_id', 'auth_provider', 'approval_status',
            ]);
        });
    }
};
