<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A winner who did not come forward loses the prize, and the record keeps
     * it: the position they had, when it was declared and by whom.
     */
    public function up(): void
    {
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('forfeited_position')->nullable()->after('winner_position');
            $table->timestamp('forfeited_at')->nullable()->after('forfeited_position');
            $table->foreignId('forfeited_by')->nullable()->after('forfeited_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forfeited_by');
            $table->dropColumn(['forfeited_position', 'forfeited_at']);
        });
    }
};
