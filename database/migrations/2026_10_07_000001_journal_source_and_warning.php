<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('source', 40)->nullable()->after('publisher');
            $table->text('warning_text')->nullable()->after('source');
        });

        DB::table('journals')->where('tier', 'D')->update(['source' => 'mahalliy OAK']);
        DB::table('journals')->where('tier', 'C')->update(['source' => 'xalqaro OAK']);
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn(['source', 'warning_text']);
        });
    }
};
