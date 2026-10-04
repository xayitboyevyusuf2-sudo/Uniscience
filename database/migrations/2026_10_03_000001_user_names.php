<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function (Blueprint $t) { $t->string('first_name',60)->nullable(); $t->string('last_name',60)->nullable(); });
  // keep existing accounts: split the old full name at the first space
  foreach (DB::table('users')->get() as $u) {
   $p = preg_split('/\s+/', trim((string)$u->name), 2);
   DB::table('users')->where('id',$u->id)->update(['first_name'=>$p[0] ?: null,'last_name'=>$p[1] ?? null]);
  }
 }
 public function down(): void {}
};
