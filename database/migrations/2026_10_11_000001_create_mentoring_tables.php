<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // mentor
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location', 190);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('mentor_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->string('message', 500);
            $table->string('status', 20)->default('pending');
            $table->string('response_note', 500)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_requests');
        Schema::dropIfExists('time_slots');
    }
};
