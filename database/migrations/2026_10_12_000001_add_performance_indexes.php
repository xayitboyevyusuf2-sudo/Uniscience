<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->index('status');
            $table->index('user_id');
            $table->index('decided_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->index('faculty');
            $table->index('category');
        });
        Schema::table('ratings', function (Blueprint $table) {
            $table->index('rank_group');
            $table->index('rank_faculty');
            $table->index('rank_university');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['decided_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['faculty']);
            $table->dropIndex(['category']);
        });
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['rank_group']);
            $table->dropIndex(['rank_faculty']);
            $table->dropIndex(['rank_university']);
        });
    }
};
