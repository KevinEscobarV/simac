<?php

use App\Models\City;
use App\Models\School;
use App\Models\Teacher;

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

test('search matches the teacher code, even typed without its leading zero', function () {
    $teacher = Teacher::factory()->create(['code' => '0321', 'document_number' => '1118541203']);
    Teacher::factory()->create(['code' => '0455', 'document_number' => '1118663019']);

    expect(Teacher::query()->search('0321')->pluck('id')->all())->toBe([$teacher->id])
        ->and(Teacher::query()->search('321')->pluck('id')->all())->toBe([$teacher->id]);
});

test('search matches the school and the municipality', function () {
    $maniSchool = School::factory()->for(City::factory()->create(['name' => 'Maní']))->create(['name' => 'IE Sagrado Corazón']);
    Teacher::factory()->for($maniSchool)->create(['name' => 'Martha Lucía Guatibonza']);
    Teacher::factory()->withoutSchool()->for(City::factory()->create(['name' => 'Yopal']))->create(['name' => 'Luz Dary Camargo']);

    expect(Teacher::query()->search('corazon')->pluck('name')->all())->toBe(['Martha Lucía Guatibonza'])
        ->and(Teacher::query()->search('mani')->pluck('name')->all())->toBe(['Martha Lucía Guatibonza']);
});
