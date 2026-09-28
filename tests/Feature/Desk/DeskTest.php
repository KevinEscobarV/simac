<?php

use App\Enums\Role;
use App\Livewire\Desk\Index;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('desk'))->assertRedirect(route('login'));
});

test('registrars and administrators use the desk, the projector does not', function (Role $role, bool $allowed) {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('desk'))->assertStatus($allowed ? 200 : 403);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, true],
    'projector' => [Role::Projector, false],
]);

test('without an open assembly the desk waits for one', function () {
    $this->actingAs(User::factory()->registrar()->create());
    Assembly::factory()->closed()->create();

    $this->get(route('desk'))
        ->assertOk()
        ->assertSee(__('The assembly is not open yet'));
});

test('Enter checks in whoever the key points to and clears the field for the next person', function () {
    $registrar = User::factory()->registrar()->create();
    $this->actingAs($registrar);
    $assembly = Assembly::factory()->create();
    $teacher = Teacher::factory()->create(['document_number' => '1118541203']);

    Livewire::test(Index::class)
        ->set('search', '1.118.541.203')
        ->call('checkInByKey', '1.118.541.203')
        ->assertHasNoErrors()
        ->assertSet('search', '')
        ->assertSet('confirmation.title', __('Check-in registered'))
        ->assertDispatched('desk-ready');

    $attendance = $assembly->attendances()->sole();

    expect($attendance->teacher->is($teacher))->toBeTrue()
        ->and($attendance->registered_by)->toBe($registrar->id);
});

test('Shift + Enter checks out whoever the key points to', function () {
    $this->actingAs(User::factory()->registrar()->create());
    $attendance = Attendance::factory()->create();

    Livewire::test(Index::class)
        ->call('checkOutByKey', $attendance->teacher->code)
        ->assertHasNoErrors()
        ->assertSet('confirmation.title', __('Check-out registered'));

    expect($attendance->refresh()->isPresent())->toBeFalse();
});

test('a key several teachers match checks nobody in and lists them to pick one', function () {
    $this->actingAs(User::factory()->registrar()->create());
    Assembly::factory()->create();
    Teacher::factory()->create(['name' => 'Ana María Rojas']);
    Teacher::factory()->create(['name' => 'Ana Lucía Pérez']);

    Livewire::test(Index::class)
        ->set('search', 'ana')
        ->call('checkInByKey', 'ana')
        ->assertHasErrors('search')
        ->assertSet('confirmation', null)
        ->assertSee('Ana María Rojas')
        ->assertSee('Ana Lucía Pérez');

    expect(Attendance::count())->toBe(0);
});

test('why a movement failed stays on screen through the next request', function () {
    $this->actingAs(User::factory()->registrar()->create());
    Assembly::factory()->create();

    // Right after an Enter comes the debounced sync of the field, with nothing new.
    Livewire::test(Index::class)
        ->call('checkInByKey', '999999999')
        ->assertHasErrors('search')
        ->call('$refresh')
        ->assertHasErrors('search');
});

test('the roll is only listed for a search term', function () {
    $this->actingAs(User::factory()->registrar()->create());
    Assembly::factory()->create();
    Teacher::factory()->create(['name' => 'Paola Andrea Sarmiento']);

    Livewire::test(Index::class)
        ->assertDontSee('Paola Andrea Sarmiento')
        ->set('search', 'paola')
        ->assertSee('Paola Andrea Sarmiento');
});

test('the movement on display can be undone', function () {
    $this->actingAs(User::factory()->registrar()->create());
    $assembly = Assembly::factory()->create();
    $teacher = Teacher::factory()->create();

    Livewire::test(Index::class)
        ->call('checkIn', $teacher->id)
        ->call('undo')
        ->assertHasNoErrors()
        ->assertSet('confirmation.title', __('Movement undone'))
        ->assertSet('confirmation.movement', null);

    expect($assembly->attendances()->count())->toBe(0);
});

test('checking in someone already present leaves nothing to undo', function () {
    $this->actingAs(User::factory()->registrar()->create());
    $attendance = Attendance::factory()->create();

    Livewire::test(Index::class)
        ->call('checkIn', $attendance->teacher_id)
        ->assertSet('confirmation.title', __('Already checked in'))
        ->assertSet('confirmation.movement', null);
});

test('the latest movements of every desk are listed, newest first', function () {
    $this->actingAs(User::factory()->registrar()->create());
    $this->freezeSecond();
    $assembly = Assembly::factory()->create();
    Attendance::factory()->for($assembly)->for(Teacher::factory()->state(['name' => 'Llegó Primero']))->create(['updated_at' => now()->subMinutes(10)]);
    Attendance::factory()->for($assembly)->for(Teacher::factory()->state(['name' => 'Llegó Después']))->create(['updated_at' => now()->subMinute()]);

    Livewire::test(Index::class)->assertSeeInOrder(['Llegó Después', 'Llegó Primero']);
});

test('a movement after someone closed the assembly explains why and registers nothing', function () {
    $this->actingAs(User::factory()->registrar()->create());
    $assembly = Assembly::factory()->create();
    $teacher = Teacher::factory()->create();

    $desk = Livewire::test(Index::class);
    $assembly->forceFill(['closed_at' => now()])->save();

    $desk->call('checkIn', $teacher->id)->assertHasErrors('search');

    expect(Attendance::count())->toBe(0);
});

test('when the assembly closes the desk goes back to waiting and clears the field', function () {
    $this->actingAs(User::factory()->registrar()->create());
    $assembly = Assembly::factory()->create();

    $desk = Livewire::test(Index::class)->set('search', 'ana');
    $assembly->forceFill(['closed_at' => now()])->save();

    $desk->call('refreshLive')
        ->assertSet('search', '')
        ->assertSee(__('The assembly is not open yet'));
});
