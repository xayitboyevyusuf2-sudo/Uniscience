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
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('type', 30)->default('journal_local_oak');
            $table->string('doi', 190)->nullable()->index();
            $table->text('annotation_uz')->nullable();
            $table->text('annotation_ru')->nullable();
            $table->text('annotation_en')->nullable();
            $table->string('keywords_uz', 500)->nullable();
            $table->string('keywords_ru', 500)->nullable();
            $table->string('keywords_en', 500)->nullable();
            $table->string('field', 500)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('articles')->whereNotNull('abstract')->update(['annotation_uz' => DB::raw('abstract')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropForeign(['decided_by']);
            $table->dropIndex(['doi']);
            $table->dropColumn([
                'type', 'doi', 'annotation_uz', 'annotation_ru', 'annotation_en',
                'keywords_uz', 'keywords_ru', 'keywords_en', 'field', 'decided_at', 'decided_by',
            ]);
        });
    }
};
