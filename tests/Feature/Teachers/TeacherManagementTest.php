<?php

use App\Livewire\Teachers\Index;
use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('teachers.index'))->assertRedirect(route('login'));
});

test('users without permission to manage the roll are forbidden', function () {
    $this->actingAs(User::factory()->registrar()->create());

    $this->get(route('teachers.index'))->assertForbidden();
});

test('the roll lists active teachers with their school and code', function () {
    $this->actingAs(User::factory()->admin()->create());
    $teacher = Teacher::factory()
        ->for(School::factory()->create(['name' => 'IE Braulio González']))
        ->create(['name' => 'Paola Andrea Sarmiento']);
    Teacher::factory()->trashed()->create(['name' => 'Jorge Eliécer Mora']);

    $this->get(route('teachers.index'))
        ->assertSeeLivewire(Index::class)
        ->assertSee('Paola Andrea Sarmiento')
        ->assertSee('IE Braulio González')
        ->assertSee($teacher->code)
        ->assertDontSee('Jorge Eliécer Mora');
});

test('the roll can be filtered by municipality, school and membership', function () {
    $this->actingAs(User::factory()->admin()->create());
    $yopal = City::factory()->create();
    $braulio = School::factory()->for($yopal)->create();
    $manuela = School::factory()->for($yopal)->create();
    Teacher::factory()->for($braulio)->create(['name' => 'Paola Andrea Sarmiento']);
    Teacher::factory()->for($manuela)->nonMember()->create(['name' => 'Carlos Andrés Pérez']);
    Teacher::factory()->create(['name' => 'Diana Patricia Salcedo']);

    Livewire::test(Index::class)
        ->set('city', (string) $yopal->id)
        ->assertSee('Paola Andrea Sarmiento')
        ->assertSee('Carlos Andrés Pérez')
        ->assertDontSee('Diana Patricia Salcedo')
        ->set('school', (string) $manuela->id)
        ->assertSee('Carlos Andrés Pérez')
        ->assertDontSee('Paola Andrea Sarmiento')
        ->set('school', '')
        ->set('membership', 'si')
        ->assertSee('Paola Andrea Sarmiento')
        ->assertDontSee('Carlos Andrés Pérez');
});

test('changing the municipality filter clears the school filter', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->set('city', (string) $school->city_id)
        ->set('school', (string) $school->id)
        ->set('city', (string) City::factory()->create()->id)
        ->assertSet('school', '');
});

test('retired teachers are listed on their own tab', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->create(['name' => 'Paola Andrea Sarmiento']);
    Teacher::factory()->trashed()->create(['name' => 'Jorge Eliécer Mora']);

    Livewire::test(Index::class)
        ->set('status', 'retirados')
        ->assertSee('Jorge Eliécer Mora')
        ->assertDontSee('Paola Andrea Sarmiento');
});

test('administrators can register a teacher, typing the ID number with dots', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.name', ' Luz Dary  Camargo ')
        ->set('form.document_number', '1.118.663.019')
        ->set('form.city_id', (string) $school->city_id)
        ->set('form.school_id', (string) $school->id)
        ->set('form.is_union_member', false)
        ->call('save')
        ->assertHasNoErrors();

    $teacher = Teacher::sole();

    expect($teacher->name)->toBe('Luz Dary Camargo')
        ->and($teacher->document_number)->toBe('1118663019')
        ->and($teacher->school->is($school))->toBeTrue()
        ->and($teacher->is_union_member)->toBeFalse();
});

test('registering a teacher requires a name, ID number, municipality and school', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors([
            'form.name' => 'required',
            'form.document_number' => 'required',
            'form.city_id' => 'required',
            'form.school_id' => 'required',
        ]);

    expect(Teacher::count())->toBe(0);
});

test('the ID number must have between 5 and 12 digits', function (string $documentNumber) {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.name', 'Luz Dary Camargo')
        ->set('form.document_number', $documentNumber)
        ->set('form.city_id', (string) $school->city_id)
        ->set('form.school_id', (string) $school->id)
        ->call('save')
        ->assertHasErrors(['form.document_number' => 'digits_between']);
})->with([
    'too short' => '1234',
    'too long' => '1234567890123',
    'with letters' => 'E-123456',
]);

test('the ID number cannot be repeated, not even with a retired teacher', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->trashed()->create(['document_number' => '1118663019']);
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.name', 'Luz Dary Camargo')
        ->set('form.document_number', '1118663019')
        ->set('form.city_id', (string) $school->city_id)
        ->set('form.school_id', (string) $school->id)
        ->call('save')
        ->assertHasErrors(['form.document_number' => 'unique']);
});

test('the school must belong to the chosen municipality', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.name', 'Luz Dary Camargo')
        ->set('form.document_number', '1118663019')
        ->set('form.city_id', (string) City::factory()->create()->id)
        ->set('form.school_id', (string) $school->id)
        ->call('save')
        ->assertHasErrors(['form.school_id' => 'exists']);
});

test('changing the municipality in the dialog clears the chosen school', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.city_id', (string) $school->city_id)
        ->set('form.school_id', (string) $school->id)
        ->set('form.city_id', (string) City::factory()->create()->id)
        ->assertSet('form.school_id', '');
});

test('administrators can edit a teacher and move them to another school, keeping their code', function () {
    $this->actingAs(User::factory()->admin()->create());
    $teacher = Teacher::factory()->create();
    $code = $teacher->code;
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->call('edit', $teacher->id)
        ->assertSet('form.document_number', $teacher->document_number)
        ->set('form.name', 'Nombre corregido')
        ->set('form.city_id', (string) $school->city_id)
        ->set('form.school_id', (string) $school->id)
        ->call('save')
        ->assertHasNoErrors();

    $teacher->refresh();

    expect($teacher->name)->toBe('Nombre corregido')
        ->and($teacher->school->is($school))->toBeTrue()
        ->and($teacher->code)->toBe($code);
});

test('a school missing from the list can be added without leaving the dialog', function () {
    $this->actingAs(User::factory()->admin()->create());
    $city = City::factory()->create();

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.city_id', (string) $city->id)
        ->call('startAddingSchool')
        ->set('newSchool.name', 'IE Sagrado Corazón')
        ->call('addSchool')
        ->assertHasNoErrors()
        ->assertSet('addingSchool', false)
        ->assertSet('form.school_id', (string) $city->schools()->sole()->id);

    expect($city->schools()->sole()->name)->toBe('IE Sagrado Corazón');
});

test('administrators can switch a teacher\'s union membership from the list', function () {
    $this->actingAs(User::factory()->admin()->create());
    $teacher = Teacher::factory()->create(['is_union_member' => true]);

    Livewire::test(Index::class)->call('toggleMembership', $teacher->id);

    expect($teacher->refresh()->is_union_member)->toBeFalse();
});

test('administrators can retire a teacher and bring them back', function () {
    $this->actingAs(User::factory()->admin()->create());
    $teacher = Teacher::factory()->create();

    $component = Livewire::test(Index::class)
        ->call('confirmRetirement', $teacher->id)
        ->call('retire');

    $this->assertSoftDeleted($teacher);

    $component->call('reincorporate', $teacher->id);

    $this->assertNotSoftDeleted($teacher);
});
