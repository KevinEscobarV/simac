<?php

use App\Livewire\Locations\Index;
use App\Models\City;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('locations.index'))->assertRedirect(route('login'));
});

test('users without permission to manage the roll are forbidden', function () {
    $this->actingAs(User::factory()->registrar()->create());

    $this->get(route('locations.index'))->assertForbidden();
});

test('municipalities are listed alphabetically ignoring accents, with their school count', function () {
    $this->actingAs(User::factory()->admin()->create());
    City::factory()->create(['name' => 'Tauramena']);
    City::factory()->has(School::factory()->count(2))->create(['name' => 'Támara']);

    $this->get(route('locations.index'))
        ->assertSeeLivewire(Index::class)
        ->assertSeeInOrder(['Támara', trans_choice('{0} No schools|{1} :count school|[2,*] :count schools', 2), 'Tauramena']);
});

test('the schools shown are those of the selected municipality, or of the first one', function () {
    $this->actingAs(User::factory()->admin()->create());
    $aguazul = City::factory()->create(['name' => 'Aguazul']);
    $yopal = City::factory()->create(['name' => 'Yopal']);
    School::factory()->for($aguazul)->create(['name' => 'IE Camilo Torres Restrepo']);
    School::factory()->for($yopal)->create(['name' => 'IE Braulio González']);

    Livewire::test(Index::class)
        ->assertSee('IE Camilo Torres Restrepo')
        ->assertDontSee('IE Braulio González')
        ->call('selectCity', $yopal->id)
        ->assertSee('IE Braulio González')
        ->assertDontSee('IE Camilo Torres Restrepo')
        ->set('cityId', 999)
        ->assertSee('IE Camilo Torres Restrepo');
});

test('searching finds schools in every municipality', function () {
    $this->actingAs(User::factory()->admin()->create());
    School::factory()->for(City::factory()->create(['name' => 'Aguazul']))->create(['name' => 'IE Camilo Torres Restrepo']);
    School::factory()->for(City::factory()->create(['name' => 'Yopal']))->create(['name' => 'IE Camilo Torres Yopal']);
    School::factory()->create(['name' => 'IE Braulio González']);

    Livewire::test(Index::class)
        ->set('search', 'camilo')
        ->assertSee('IE Camilo Torres Restrepo')
        ->assertSee('IE Camilo Torres Yopal')
        ->assertDontSee('IE Braulio González');
});

test('administrators can create a municipality, which becomes the selected one', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('createCity')
        ->set('cityForm.name', '  San Luis   de Palenque ')
        ->call('saveCity')
        ->assertHasNoErrors()
        ->assertSet('cityId', City::firstWhere('name', 'San Luis de Palenque')?->id);
});

test('a municipality name is required and cannot be repeated, even with stray spaces', function () {
    $this->actingAs(User::factory()->admin()->create());
    City::factory()->create(['name' => 'Yopal']);

    Livewire::test(Index::class)
        ->call('createCity')
        ->call('saveCity')
        ->assertHasErrors(['cityForm.name' => 'required'])
        ->set('cityForm.name', ' Yopal ')
        ->call('saveCity')
        ->assertHasErrors(['cityForm.name' => 'unique']);

    expect(City::count())->toBe(1);
});

test('administrators can rename a municipality', function () {
    $this->actingAs(User::factory()->admin()->create());
    $city = City::factory()->create(['name' => 'Paz de Aripor']);

    Livewire::test(Index::class)
        ->call('editCity', $city->id)
        ->assertSet('cityForm.name', 'Paz de Aripor')
        ->set('cityForm.name', 'Paz de Ariporo')
        ->call('saveCity')
        ->assertHasNoErrors();

    expect($city->refresh()->name)->toBe('Paz de Ariporo');
});

test('administrators can delete a municipality without schools', function () {
    $this->actingAs(User::factory()->admin()->create());
    $city = City::factory()->create();

    Livewire::test(Index::class)
        ->call('confirmCityDeletion', $city->id)
        ->assertSet('cityDeletionBlocker', null)
        ->call('deleteCity')
        ->assertHasNoErrors();

    $this->assertModelMissing($city);
});

test('a municipality that still has schools cannot be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());
    $city = City::factory()->create();

    $component = Livewire::test(Index::class)->call('confirmCityDeletion', $city->id);

    School::factory()->for($city)->count(3)->create();

    $component->call('deleteCity')->assertHasErrors('city');

    $this->assertModelExists($city);
});

test('the deletion dialog explains what still depends on the municipality', function () {
    $this->actingAs(User::factory()->admin()->create());
    $city = City::factory()->has(School::factory()->count(3))->create();

    Livewire::test(Index::class)
        ->call('confirmCityDeletion', $city->id)
        ->assertSee(trans_choice('It still has :count school. Move or delete it first.|It still has :count schools. Move or delete them first.', 3));
});

test('administrators can add a school to the selected municipality', function () {
    $this->actingAs(User::factory()->admin()->create());
    City::factory()->create(['name' => 'Aguazul']);
    $yopal = City::factory()->create(['name' => 'Yopal']);

    Livewire::test(Index::class)
        ->call('selectCity', $yopal->id)
        ->set('newSchool.name', ' IE  Manuela Beltrán ')
        ->call('addSchool')
        ->assertHasNoErrors()
        ->assertSet('newSchool.name', '');

    expect($yopal->schools()->pluck('name')->all())->toBe(['IE Manuela Beltrán']);
});

test('a school name is unique within its municipality only', function () {
    $this->actingAs(User::factory()->admin()->create());
    $aguazul = City::factory()->create(['name' => 'Aguazul']);
    $yopal = City::factory()->create(['name' => 'Yopal']);
    School::factory()->for($yopal)->create(['name' => 'IE Sagrado Corazón']);

    Livewire::test(Index::class)
        ->call('selectCity', $yopal->id)
        ->set('newSchool.name', 'IE Sagrado Corazón')
        ->call('addSchool')
        ->assertHasErrors(['newSchool.name' => 'unique'])
        ->call('selectCity', $aguazul->id)
        ->set('newSchool.name', 'IE Sagrado Corazón')
        ->call('addSchool')
        ->assertHasNoErrors();

    expect(School::where('name', 'IE Sagrado Corazón')->count())->toBe(2);
});

test('administrators can rename a school and move it to another municipality', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create(['name' => 'IE Siglo 21']);
    $tauramena = City::factory()->create(['name' => 'Tauramena']);

    Livewire::test(Index::class)
        ->call('editSchool', $school->id)
        ->set('schoolForm.name', 'IE Siglo XXI')
        ->set('schoolForm.city_id', (string) $tauramena->id)
        ->call('saveSchool')
        ->assertHasNoErrors();

    $school->refresh();

    expect($school->name)->toBe('IE Siglo XXI')
        ->and($school->city->is($tauramena))->toBeTrue();
});

test('administrators can delete a school', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();

    Livewire::test(Index::class)
        ->call('confirmSchoolDeletion', $school->id)
        ->call('deleteSchool');

    $this->assertModelMissing($school);
});
