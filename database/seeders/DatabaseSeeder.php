<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database for local development: one user per role,
     * all with the password "password".
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@simac.test',
        ]);

        User::factory()->registrar()->create([
            'name' => 'Mesa de registro',
            'email' => 'registro@simac.test',
        ]);

        User::factory()->projector()->create([
            'name' => 'Proyector',
            'email' => 'pantalla@simac.test',
        ]);
    }
}
