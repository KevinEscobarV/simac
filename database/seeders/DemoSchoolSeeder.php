<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * The schools of the original demo, for local development only.
 */
class DemoSchoolSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const array SCHOOLS = [
        'Yopal' => ['IE Braulio González', 'IE Manuela Beltrán', 'IE La Presentación'],
        'Tauramena' => ['IE Siglo XXI', 'IE José María Córdoba'],
        'Aguazul' => ['IE Camilo Torres Restrepo'],
        'Villanueva' => ['IE Ezequiel Moreno y Díaz'],
        'Paz de Ariporo' => ['IE Sagrado Corazón'],
    ];

    public function run(): void
    {
        foreach (self::SCHOOLS as $cityName => $schools) {
            $city = City::firstOrCreate(['name' => $cityName]);

            foreach ($schools as $schoolName) {
                School::firstOrCreate(['city_id' => $city->id, 'name' => $schoolName]);
            }
        }
    }
}
