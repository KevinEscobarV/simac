<?php

use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use App\Support\RaffleFilters;

test('each filter narrows who takes part', function (Closure $filters, array $expected) {
    $yopal = City::factory()->create(['name' => 'Yopal']);
    $school = School::factory()->for($yopal)->create(['name' => 'IE Braulio González']);
    $assembly = Assembly::factory()->create();

    Teacher::factory()->for($school)->create(['name' => 'Afiliada Presente']);
    Teacher::factory()->for($school)->nonMember()->create(['name' => 'No Afiliado']);
    Teacher::factory()->for(School::factory()->for($yopal))->create(['name' => 'Otro Colegio']);
    Teacher::factory()->create(['name' => 'Otro Municipio']);
    Teacher::factory()->trashed()->create(['name' => 'Retirada']);
    Attendance::factory()->for($assembly)->for(Teacher::firstWhere('name', 'Afiliada Presente'))->create();

    $names = $filters($yopal, $school)->participants($assembly)->orderBy('name')->pluck('name')->all();

    expect($names)->toBe($expected);
})->with([
    'nobody left out but the retired' => [fn () => new RaffleFilters, ['Afiliada Presente', 'No Afiliado', 'Otro Colegio', 'Otro Municipio']],
    'union members' => [fn () => new RaffleFilters(unionMembersOnly: true), ['Afiliada Presente', 'Otro Colegio', 'Otro Municipio']],
    'municipality' => [fn (City $city) => new RaffleFilters(cityId: $city->id), ['Afiliada Presente', 'No Afiliado', 'Otro Colegio']],
    'school' => [fn (City $city, School $school) => new RaffleFilters(schoolId: $school->id), ['Afiliada Presente', 'No Afiliado']],
    'those present' => [fn () => new RaffleFilters(presentOnly: true), ['Afiliada Presente']],
]);

test('without an open assembly nobody counts as present', function () {
    Teacher::factory()->count(2)->create();

    expect((new RaffleFilters(presentOnly: true))->participants(null)->count())->toBe(0);
});

test('the record describes the filters in words', function () {
    $school = School::factory()->for(City::factory()->state(['name' => 'Yopal']))->create(['name' => 'IE Braulio González']);
    $filters = new RaffleFilters(unionMembersOnly: true, cityId: $school->city_id, schoolId: $school->id, presentOnly: true);

    expect($filters->describe(Assembly::factory()->create()))
        ->toBe(implode(' · ', [__('Only those present'), __('Only union members'), 'Yopal', 'IE Braulio González', __('Without earlier winners')]))
        ->and((new RaffleFilters)->describe(null))->toBe(__('All teachers'));
});
