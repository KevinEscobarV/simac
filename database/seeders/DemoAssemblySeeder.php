<?php

namespace Database\Seeders;

use App\Enums\QuorumType;
use App\Models\Assembly;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * An open assembly, as in the original demo, so attendance can be tried out
 * right away. For local development only.
 */
class DemoAssemblySeeder extends Seeder
{
    public function run(): void
    {
        if (Assembly::query()->open()->exists()) {
            return;
        }

        Assembly::create([
            'name' => 'Asamblea General Ordinaria',
            'date' => today(),
            'location' => 'Sede sindical · Yopal',
            // Half of the members, the usual quorum of a union statute.
            'quorum_type' => QuorumType::Percentage,
            'quorum_value' => 50,
            'opened_by' => User::firstWhere('email', 'admin@simac.test')?->id,
        ]);
    }
}
