<?php

use App\Actions\Attendance\ResolveTeacher;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

test('a teacher is found by ID number, typed with or without dots', function () {
    $teacher = Teacher::factory()->create(['document_number' => '1118541203']);
    Teacher::factory()->create(['document_number' => '1118541204']);

    expect(app(ResolveTeacher::class)->handle('1.118.541.203')->is($teacher))->toBeTrue();
});

test('a teacher is found by code', function () {
    $teacher = Teacher::factory()->create(['code' => '0321']);
    Teacher::factory()->count(2)->create();

    expect(app(ResolveTeacher::class)->handle(' 0321 ')->is($teacher))->toBeTrue();
});

test('the code goes before an ID number that reads the same', function () {
    $byCode = Teacher::factory()->create(['code' => '54321']);
    Teacher::factory()->create(['document_number' => '54321']);

    expect(app(ResolveTeacher::class)->handle('54321')->is($byCode))->toBeTrue();
});

test('a teacher is found by name when only one matches', function () {
    $teacher = Teacher::factory()->create(['name' => 'Héctor Fabio Niño']);
    Teacher::factory()->create(['name' => 'Luz Dary Camargo']);

    expect(app(ResolveTeacher::class)->handle('hector')->is($teacher))->toBeTrue();
});

test('it asks to pick from the list when several teachers match', function () {
    Teacher::factory()->create(['name' => 'Luz Dary Camargo']);
    Teacher::factory()->create(['name' => 'Luz Marina Rojas']);

    try {
        app(ResolveTeacher::class)->handle('luz');
        $this->fail('Two teachers match, it should not resolve.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['key'][0])
            ->toBe(__(':count teachers match “:key”: pick one from the list.', ['count' => 2, 'key' => 'luz']));
    }
});

test('it fails when nobody matches, and retired teachers are not found', function (string $key) {
    Teacher::factory()->trashed()->create(['name' => 'Jorge Eliécer Mora', 'document_number' => '1118392856']);

    expect(fn () => app(ResolveTeacher::class)->handle($key))->toThrow(ValidationException::class);
})->with([
    'nobody' => 'Nadie',
    'retired by name' => 'Jorge',
    'retired by ID number' => '1118392856',
    'nothing typed' => '  ',
]);
