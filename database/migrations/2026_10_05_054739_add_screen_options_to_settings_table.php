<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the projection screen tells the room about each raffle. Union
     * membership stays off it unless someone turns it on.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('screen_shows_membership')->default(false)->after('event_image_path');
            $table->boolean('screen_shows_participants')->default(true)->after('screen_shows_membership');
            $table->boolean('screen_shows_filters')->default(true)->after('screen_shows_participants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['screen_shows_membership', 'screen_shows_participants', 'screen_shows_filters']);
        });
    }
};
