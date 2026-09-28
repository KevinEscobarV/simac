<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['cities', 'schools'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('normalized_name')->default('')->after('name');
            });

            foreach (DB::table($tableName)->select('id', 'name')->lazyById() as $row) {
                DB::table($tableName)->where('id', $row->id)->update([
                    'normalized_name' => Str::lower(Str::ascii(Str::squish($row->name))),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['cities', 'schools'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('normalized_name');
            });
        }
    }
};
