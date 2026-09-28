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
        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->nullable()->constrained()->nullOnDelete();
            $table->string('prize');
            $table->unsignedSmallInteger('winners_count');
            $table->string('animation');
            $table->json('filters');
            $table->string('filter_description');
            $table->unsignedInteger('participants_count');
            $table->boolean('quorum_met')->nullable();
            $table->foreignId('drawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('drawn_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffles');
    }
};
