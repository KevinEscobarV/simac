<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The teacher code stops being derived from the id: the union assigns it,
     * only digits. The teachers already on the roll keep the number their old
     * code carried (SIM-003 becomes 0003).
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('document_number');
        });

        DB::table('teachers')->select('id')->orderBy('id')->chunkById(500, function (Collection $teachers): void {
            foreach ($teachers as $teacher) {
                DB::table('teachers')->where('id', $teacher->id)->update(['code' => sprintf('%04d', $teacher->id)]);
            }
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->string('code', 10)->nullable(false)->change();
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
