<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function (Blueprint $t) {
   $t->string('student_id',30)->nullable()->unique();
   $t->string('faculty')->nullable(); $t->string('direction')->nullable();
   $t->unsignedTinyInteger('course')->nullable();
   $t->string('role',20)->default('student'); $t->boolean('blocked')->default(false);
   $t->timestamp('consent_at')->nullable();
  });
  Schema::create('journals', function (Blueprint $t) {
   $t->id(); $t->string('issn',9)->nullable()->index(); $t->string('name'); $t->string('name_norm')->nullable()->index(); $t->string('field',500);
   $t->enum('tier',['A','B','C','D','E','X']); $t->date('listed_from'); $t->date('listed_to')->nullable();
   $t->string('country')->nullable(); $t->string('publisher')->nullable(); $t->timestamps();
   $t->unique(['issn','listed_from']); // history is kept, rows are never deleted
  });
  Schema::create('articles', function (Blueprint $t) {
   $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
   $t->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
   $t->string('title',500); $t->string('journal_name'); $t->string('issn',9)->nullable()->index();
   $t->date('published_at'); $t->string('url',500); $t->string('pdf_path')->nullable();
   $t->text('abstract')->nullable(); $t->string('coauthors',500)->nullable();
   $t->string('position',20); $t->string('status',20)->default('pending'); $t->string('reason')->nullable();
   $t->timestamps(); $t->unique(['url','issn']);
  });
  Schema::create('review_logs', function (Blueprint $t) {
   $t->id(); $t->foreignId('article_id')->constrained()->cascadeOnDelete();
   $t->foreignId('user_id')->nullable(); $t->string('decision',20); $t->string('note',500)->nullable(); $t->timestamps();
  });
  Schema::create('certificates', function (Blueprint $t) {
   $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('token',40)->unique(); $t->timestamps();
  });
  Schema::create('settings', function (Blueprint $t) { $t->string('key')->primary(); $t->string('value')->nullable(); $t->timestamps(); });
  Schema::create('setting_changes', function (Blueprint $t) {
   $t->id(); $t->string('key'); $t->string('old')->nullable(); $t->string('new')->nullable(); $t->foreignId('user_id')->nullable(); $t->timestamp('created_at')->nullable();
  });
 }
 public function down(): void {}
};
