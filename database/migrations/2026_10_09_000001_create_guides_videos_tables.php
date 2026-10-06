<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 60)->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->integer('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 60)->nullable();
            $table->string('file_path');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->integer('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('content_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('viewable_type');
            $table->unsignedBigInteger('viewable_id');
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'viewable_type', 'viewable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_views');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('guides');
    }
};
