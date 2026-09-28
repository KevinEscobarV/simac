<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('raffle_entries', function (Blueprint $table) {
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('winner_position')->nullable();

            $table->primary(['raffle_id', 'teacher_id']);
            $table->unique(['raffle_id', 'winner_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_entries');
    }
};
