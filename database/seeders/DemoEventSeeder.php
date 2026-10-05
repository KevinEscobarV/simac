<?php

namespace Database\Seeders;

use App\Enums\QuorumType;
use App\Models\Assembly;
use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The data for a live demo, on top of the development data: a roll of 200
 * teachers across Casanare and 20 registration desks (mesa01@simac.test to
 * mesa20@simac.test, password "password"). For local development only:
 *
 *     php artisan migrate:fresh --seeder=DemoEventSeeder
 *
 * The same seed gives the same roll every time.
 */
class DemoEventSeeder extends Seeder
{
    private const int ROLL = 200;

    private const int DESKS = 20;

    /** Within reach of a demo: the open assembly asks for this many members present. */
    private const int QUORUM = 30;

    /**
     * Schools added to the demo's, and how many teachers each one draws
     * compared to the others.
     *
     * @var array<string, array<string, int>>
     */
    private const array SCHOOLS = [
        'Yopal' => ['IE Braulio González' => 3, 'IE Manuela Beltrán' => 3, 'IE La Presentación' => 3, 'IE Carlos Lleras Restrepo' => 3, 'IE Luis Hernández Vargas' => 3, 'IE Antonio Nariño' => 2, 'IE Policarpa Salavarrieta' => 2],
        'Aguazul' => ['IE Camilo Torres Restrepo' => 2, 'IE Jorge Eliécer Gaitán' => 2, 'IE San Agustín' => 1],
        'Tauramena' => ['IE Siglo XXI' => 2, 'IE José María Córdoba' => 2],
        'Villanueva' => ['IE Ezequiel Moreno y Díaz' => 2, 'IE Fabio Riveros' => 1],
        'Paz de Ariporo' => ['IE Sagrado Corazón' => 2, 'IE Juan José Rondón' => 1],
        'Monterrey' => ['IE Nuestra Señora del Carmen' => 1],
        'Maní' => ['IE Santa Teresa' => 1],
        'Orocué' => ['IE Luis Carlos Galán Sarmiento' => 1],
        'Trinidad' => ['IE Francisco José de Caldas' => 1],
        'Hato Corozal' => ['IE Simón Bolívar' => 1],
        'Pore' => ['IE Agropecuaria La Esperanza' => 1],
        'Nunchía' => ['IE San José' => 1],
        'San Luis de Palenque' => ['IE Sagrada Familia' => 1],
        'Támara' => ['IE Juan Nepomuceno Cadavid' => 1],
    ];

    private const array FIRST_NAMES = [
        'María Fernanda', 'Luz Marina', 'Ana Milena', 'Claudia Patricia', 'Sandra Liliana', 'Diana Carolina', 'Yolanda',
        'Martha Cecilia', 'Gloria Esperanza', 'Nubia', 'Blanca Inés', 'Adriana', 'Paola Andrea', 'Leidy Johana',
        'Yenny Paola', 'Carmen Rosa', 'Olga Lucía', 'Doris', 'Luz Dary', 'Nelly', 'Mónica', 'Liliana', 'Érika',
        'Angélica María', 'Yuli Andrea', 'Deisy', 'Marleny', 'Esperanza', 'Lina Marcela', 'Rocío',
        'Carlos Andrés', 'Jorge Eliécer', 'José Luis', 'Luis Alberto', 'Jhon Fredy', 'Wilson', 'Édgar', 'Fredy Alonso',
        'Héctor Fabio', 'Óscar Iván', 'Nelson', 'Ramiro', 'Hernando', 'Álvaro', 'Julio César', 'Miguel Ángel',
        'Juan Carlos', 'Diego Fernando', 'William', 'Ricardo', 'Germán', 'Fabio', 'Orlando', 'Yesid', 'Jairo',
        'Rubén Darío', 'Néstor', 'Arley', 'Édison', 'Raúl',
    ];

    private const array SURNAMES = [
        'Rojas', 'Pérez', 'Camargo', 'Mora', 'Vargas', 'Niño', 'Salcedo', 'Barrera', 'Bohórquez', 'Talero', 'Achagua',
        'Cárdenas', 'Cristancho', 'Pidiache', 'Guatibonza', 'Curcho', 'Sarmiento', 'Leguizamón', 'Rodríguez', 'Gómez',
        'Martínez', 'Hernández', 'López', 'González', 'Díaz', 'Torres', 'Ramírez', 'Suárez', 'Castro', 'Pinto', 'Ávila',
        'Reyes', 'Chaparro', 'Tumay', 'Parales', 'Aguilar', 'Moreno', 'Pulido', 'Cely', 'Pacheco', 'Silva', 'Medina',
        'Cáceres', 'Riaño', 'Galindo', 'Sánchez', 'Fonseca', 'Alarcón', 'Benítez', 'Ortiz',
    ];

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $random = new Randomizer(new Mt19937(2026));

        $this->completeRoll($random, $this->schools());
        $this->createDesks();

        Assembly::current()?->update([
            'quorum_type' => QuorumType::Count,
            'quorum_value' => self::QUORUM,
        ]);
    }

    /**
     * Every school of the demo, repeated as many times as its weight, so a
     * random pick favors the big ones.
     *
     * @return list<int>
     */
    private function schools(): array
    {
        $weighted = [];

        foreach (self::SCHOOLS as $cityName => $schools) {
            $city = City::firstOrCreate(['name' => $cityName]);

            foreach ($schools as $schoolName => $weight) {
                $school = School::firstOrCreate(['city_id' => $city->id, 'name' => $schoolName]);

                array_push($weighted, ...array_fill(0, $weight, $school->id));
            }
        }

        return $weighted;
    }

    /**
     * New teachers until the roll counts ROLL, with names and ID numbers that
     * do not repeat. About three in four are union members.
     *
     * @param  list<int>  $schools
     */
    private function completeRoll(Randomizer $random, array $schools): void
    {
        $names = array_fill_keys(Teacher::withTrashed()->pluck('name')->all(), true);
        $documents = array_fill_keys(Teacher::withTrashed()->pluck('document_number')->all(), true);

        $missing = self::ROLL - Teacher::count();
        $lastCode = (int) Teacher::withTrashed()->pluck('code')->max();
        $cityOf = School::query()->whereKey(array_unique($schools))->pluck('city_id', 'id');

        for ($created = 0; $created < $missing; $created++) {
            do {
                $name = $this->pick($random, self::FIRST_NAMES).' '.$this->pick($random, self::SURNAMES)
                    .($random->getInt(0, 1) === 1 ? ' '.$this->pick($random, self::SURNAMES) : '');
            } while (isset($names[$name]));

            do {
                // Most ID numbers issued in Casanare have ten digits; the older ones, eight.
                $document = $random->getInt(1, 100) <= 85
                    ? $this->pick($random, ['1118', '1116', '1117', '1119', '1115']).str_pad((string) $random->getInt(0, 999999), 6, '0', STR_PAD_LEFT)
                    : $this->pick($random, ['47', '74']).str_pad((string) $random->getInt(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (isset($documents[$document]));

            $names[$name] = $documents[$document] = true;

            $school = $schools[$random->getInt(0, count($schools) - 1)];

            Teacher::create([
                'school_id' => $school,
                'city_id' => $cityOf[$school],
                'name' => $name,
                'document_number' => $document,
                'code' => sprintf('%04d', ++$lastCode),
                'is_union_member' => $random->getInt(1, 100) <= 75,
            ]);
        }
    }

    /**
     * One registrar account per desk: each check-in keeps who registered it.
     */
    private function createDesks(): void
    {
        for ($desk = 1; $desk <= self::DESKS; $desk++) {
            $email = sprintf('mesa%02d@simac.test', $desk);

            if (User::where('email', $email)->exists()) {
                continue;
            }

            User::factory()->registrar()->create([
                'name' => sprintf('Mesa %02d', $desk),
                'email' => $email,
            ]);
        }
    }

    /**
     * @template T
     *
     * @param  list<T>  $options
     * @return T
     */
    private function pick(Randomizer $random, array $options): mixed
    {
        return $options[$random->getInt(0, count($options) - 1)];
    }
}
