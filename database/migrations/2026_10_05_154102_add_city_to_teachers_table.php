<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A teacher belongs to a municipality of their own and the school becomes
     * optional: the union's roll only says where each one works. The teachers
     * already on the roll take their school's municipality.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('school_id')->constrained()->restrictOnDelete();
        });

        DB::table('teachers')->update([
            'city_id' => DB::raw('(select schools.city_id from schools where schools.id = teachers.school_id)'),
        ]);

        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable(false)->change();
            $table->foreignId('school_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations. It fails while a teacher has no school.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
