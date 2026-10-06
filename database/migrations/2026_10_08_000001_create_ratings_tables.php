<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->decimal('score', 8, 2)->default(0);
            $table->unsignedInteger('rank_group')->nullable();
            $table->unsignedInteger('rank_faculty')->nullable();
            $table->unsignedInteger('rank_university')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rating_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->decimal('w_soha', 4, 2);
            $table->decimal('w_daraja', 4, 2);
            $table->decimal('w_muallif', 4, 2);
            $table->decimal('w_sana', 4, 2);
            $table->decimal('points', 8, 2);
            $table->boolean('counted')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_items');
        Schema::dropIfExists('ratings');
    }
};
