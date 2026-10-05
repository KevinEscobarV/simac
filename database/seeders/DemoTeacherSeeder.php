<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * The 18 teachers of the original demo, for local development only. Runs
 * after DemoSchoolSeeder, which creates their schools.
 */
class DemoTeacherSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: bool}>
     */
    private const array TEACHERS = [
        ['1118541203', 'María Fernanda Rojas', 'IE Braulio González', 'Yopal', true],
        ['1118207744', 'Carlos Andrés Pérez', 'IE Braulio González', 'Yopal', false],
        ['1118663019', 'Luz Dary Camargo', 'IE Manuela Beltrán', 'Yopal', true],
        ['1118392856', 'Jorge Eliécer Mora', 'IE Manuela Beltrán', 'Yopal', true],
        ['1118475130', 'Sandra Milena Vargas', 'IE La Presentación', 'Yopal', false],
        ['1118920467', 'Héctor Fabio Niño', 'IE La Presentación', 'Yopal', true],
        ['1116284095', 'Diana Patricia Salcedo', 'IE Siglo XXI', 'Tauramena', true],
        ['1116730582', 'Wilson Yesid Barrera', 'IE Siglo XXI', 'Tauramena', false],
        ['1116015874', 'Ana Rocío Bohórquez', 'IE José María Córdoba', 'Tauramena', true],
        ['1116448260', 'Fredy Alonso Talero', 'IE José María Córdoba', 'Tauramena', true],
        ['1117359418', 'Gloria Inés Achagua', 'IE Camilo Torres Restrepo', 'Aguazul', false],
        ['1117802935', 'Óscar Iván Cárdenas', 'IE Camilo Torres Restrepo', 'Aguazul', true],
        ['1119546073', 'Yolanda Cristancho', 'IE Ezequiel Moreno y Díaz', 'Villanueva', true],
        ['1119118649', 'Nelson Ricardo Pidiache', 'IE Ezequiel Moreno y Díaz', 'Villanueva', false],
        ['1115673284', 'Martha Lucía Guatibonza', 'IE Sagrado Corazón', 'Paz de Ariporo', true],
        ['1115240917', 'Edgar Mauricio Curcho', 'IE Sagrado Corazón', 'Paz de Ariporo', true],
        ['1118084526', 'Paola Andrea Sarmiento', 'IE Braulio González', 'Yopal', true],
        ['1117691350', 'Rubén Darío Leguizamón', 'IE Camilo Torres Restrepo', 'Aguazul', true],
    ];

    public function run(): void
    {
        foreach (self::TEACHERS as $index => [$documentNumber, $name, $schoolName, $cityName, $isUnionMember]) {
            $school = City::where('name', $cityName)->firstOrFail()
                ->schools()->where('name', $schoolName)->firstOrFail();

            Teacher::firstOrCreate(['document_number' => $documentNumber], [
                'school_id' => $school->id,
                'name' => $name,
                'code' => sprintf('%04d', $index + 1),
                'is_union_member' => $isUnionMember,
            ]);
        }
    }
}
