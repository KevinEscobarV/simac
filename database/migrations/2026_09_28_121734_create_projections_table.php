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
        Schema::create('projections', function (Blueprint $table) {
            $table->id();
            $table->string('phase');
            $table->foreignId('raffle_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('attempt')->default(0);
            $table->unsignedSmallInteger('winner_position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projections');
    }
};
