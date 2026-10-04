<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function (Blueprint $t) { $t->text('bio')->nullable(); $t->string('interests',200)->nullable(); $t->string('photo_path')->nullable(); });
 }
 public function down(): void {}
};
