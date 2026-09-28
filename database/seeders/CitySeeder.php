<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

/**
 * The 19 municipalities of Casanare. Safe to run on a production database:
 * it only adds the ones that are missing.
 */
class CitySeeder extends Seeder
{
    public const array MUNICIPALITIES = [
        'Aguazul',
        'Chámeza',
        'Hato Corozal',
        'La Salina',
        'Maní',
        'Monterrey',
        'Nunchía',
        'Orocué',
        'Paz de Ariporo',
        'Pore',
        'Recetor',
        'Sabanalarga',
        'Sácama',
        'San Luis de Palenque',
        'Támara',
        'Tauramena',
        'Trinidad',
        'Villanueva',
        'Yopal',
    ];

    public function run(): void
    {
        foreach (self::MUNICIPALITIES as $name) {
            City::firstOrCreate(['name' => $name]);
        }
    }
}
