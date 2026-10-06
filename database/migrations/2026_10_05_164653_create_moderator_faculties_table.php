<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('moderator_faculties', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('faculty', 120);
            $table->unique(['user_id', 'faculty']);
        });

        DB::table('users')->where('role', 'moderator')->whereNotNull('faculty')->where('faculty', '!=', '')->get(['id', 'faculty'])->each(function (object $moderator): void {
            DB::table('moderator_faculties')->insertOrIgnore(['user_id' => $moderator->id, 'faculty' => $moderator->faculty]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderator_faculties');
    }
};
