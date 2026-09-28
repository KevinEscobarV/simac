<?php

use App\Models\City;
use Database\Seeders\CitySeeder;

test('seeds the 19 municipalities of Casanare without duplicating them', function () {
    City::factory()->create(['name' => 'Yopal']);

    $this->seed(CitySeeder::class);
    $this->seed(CitySeeder::class);

    expect(City::count())->toBe(19)
        ->and(City::pluck('name')->all())->toEqualCanonicalizing(CitySeeder::MUNICIPALITIES);
});
