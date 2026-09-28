<?php

use App\Models\City;
use App\Models\School;
use App\Models\Teacher;

test('the teacher code is derived from the id', function () {
    $teacher = Teacher::factory()->create();

    expect($teacher->code)->toBe(sprintf('SIM-%03d', $teacher->id))
        ->and(Teacher::idFromCode($teacher->code))->toBe($teacher->id);
});

test('a teacher code is read in any of its usual spellings', function (string $code, ?int $id) {
    expect(Teacher::idFromCode($code))->toBe($id);
})->with([
    'canonical' => ['SIM-012', 12],
    'lowercase without padding' => ['sim-12', 12],
    'without dash' => ['SIM12', 12],
    'above 999' => ['SIM-1234', 1234],
    'zero' => ['SIM-000', null],
    'not a code' => ['1118541203', null],
]);

test('search matches the name ignoring accents and case', function () {
    Teacher::factory()->create(['name' => 'Héctor Fabio Niño']);
    Teacher::factory()->create(['name' => 'Luz Dary Camargo']);

    expect(Teacher::query()->search('hector nino')->pluck('name')->all())->toBe(['Héctor Fabio Niño']);
});

test('search matches the ID number, even typed with dots', function () {
    Teacher::factory()->create(['name' => 'Héctor Fabio Niño', 'document_number' => '1118920467']);
    Teacher::factory()->create(['name' => 'Luz Dary Camargo', 'document_number' => '1118663019']);

    expect(Teacher::query()->search('1.118.920')->pluck('name')->all())->toBe(['Héctor Fabio Niño']);
});

test('search matches the teacher code', function () {
    $teacher = Teacher::factory()->create();
    Teacher::factory()->count(2)->create();

    expect(Teacher::query()->search(strtolower($teacher->code))->pluck('id')->all())->toBe([$teacher->id]);
});

test('search matches the school and the municipality', function () {
    $maniSchool = School::factory()->for(City::factory()->create(['name' => 'Maní']))->create(['name' => 'IE Sagrado Corazón']);
    Teacher::factory()->for($maniSchool)->create(['name' => 'Martha Lucía Guatibonza']);
    Teacher::factory()->create(['name' => 'Luz Dary Camargo']);

    expect(Teacher::query()->search('corazon')->pluck('name')->all())->toBe(['Martha Lucía Guatibonza'])
        ->and(Teacher::query()->search('mani')->pluck('name')->all())->toBe(['Martha Lucía Guatibonza']);
});
