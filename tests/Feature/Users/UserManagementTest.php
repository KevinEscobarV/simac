<?php

use App\Enums\Role;
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('users without permission to manage users are forbidden', function () {
    $this->actingAs(User::factory()->registrar()->create());

    $this->get(route('users.index'))->assertForbidden();
});

test('administrators see the list of users with their roles', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->registrar()->create(['name' => 'Luz Dary Camargo']);

    $this->get(route('users.index'))
        ->assertSeeLivewire(Index::class)
        ->assertSee('Luz Dary Camargo')
        ->assertSee(Role::Registrar->label());
});

test('the list can be searched by name or email', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['name' => 'Luz Dary Camargo', 'email' => 'luz@simac.test']);
    User::factory()->create(['name' => 'Jorge Mora', 'email' => 'jorge@simac.test']);

    Livewire::test(Index::class)
        ->set('search', 'camargo')
        ->assertSee('Luz Dary Camargo')
        ->assertDontSee('Jorge Mora')
        ->set('search', 'jorge@')
        ->assertSee('Jorge Mora')
        ->assertDontSee('Luz Dary Camargo');
});

test('the list can be filtered by role', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->registrar()->create(['name' => 'Luz Dary Camargo']);
    User::factory()->projector()->create(['name' => 'Jorge Mora']);

    Livewire::test(Index::class)
        ->set('role', Role::Projector->value)
        ->assertSee('Jorge Mora')
        ->assertDontSee('Luz Dary Camargo');
});

test('administrators can create a user with a role', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.name', 'Luz Dary Camargo')
        ->set('form.email', 'luz@simac.test')
        ->set('form.role', Role::Registrar->value)
        ->set('form.password', 'secreto-largo-123')
        ->set('form.password_confirmation', 'secreto-largo-123')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::firstWhere('email', 'luz@simac.test');

    expect($user->name)->toBe('Luz Dary Camargo')
        ->and($user->assignedRole())->toBe(Role::Registrar)
        ->and(Hash::check('secreto-largo-123', $user->password))->toBeTrue();
});

test('creating a user requires a name, email, role and password', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors([
            'form.name' => 'required',
            'form.email' => 'required',
            'form.role' => 'required',
            'form.password' => 'required',
        ]);

    expect(User::count())->toBe(1);
});

test('creating a user rejects an email that is already taken', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['email' => 'luz@simac.test']);

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.name', 'Luz Dary Camargo')
        ->set('form.email', 'luz@simac.test')
        ->set('form.role', Role::Registrar->value)
        ->set('form.password', 'secreto-largo-123')
        ->set('form.password_confirmation', 'secreto-largo-123')
        ->call('save')
        ->assertHasErrors(['form.email' => 'unique']);

    expect(User::count())->toBe(2);
});

test('creating a user requires the password to be confirmed', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->set('form.name', 'Luz Dary Camargo')
        ->set('form.email', 'luz@simac.test')
        ->set('form.role', Role::Registrar->value)
        ->set('form.password', 'secreto-largo-123')
        ->set('form.password_confirmation', 'otra-cosa')
        ->call('save')
        ->assertHasErrors(['form.password' => 'confirmed']);

    $this->assertDatabaseMissing('users', ['email' => 'luz@simac.test']);
});

test('administrators can edit the details and role of a user', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->registrar()->create();

    Livewire::test(Index::class)
        ->call('edit', $user->id)
        ->assertSet('form.role', Role::Registrar->value)
        ->set('form.name', 'Nombre corregido')
        ->set('form.email', 'corregido@simac.test')
        ->set('form.role', Role::Projector->value)
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Nombre corregido')
        ->and($user->email)->toBe('corregido@simac.test')
        ->and($user->assignedRole())->toBe(Role::Projector);
});

test('administrators can edit their own details but not their role', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('edit', $admin->id)
        ->set('form.name', 'Nombre corregido')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(Index::class)
        ->call('edit', $admin->id)
        ->set('form.role', Role::Registrar->value)
        ->call('save')
        ->assertForbidden();

    expect($admin->refresh()->name)->toBe('Nombre corregido')
        ->and($admin->assignedRole())->toBe(Role::Admin);
});

test('administrators can set a new password for a user', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();

    Livewire::test(Index::class)
        ->call('editPassword', $user->id)
        ->set('passwordForm.password', 'nueva-clave-123')
        ->set('passwordForm.password_confirmation', 'nueva-clave-123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('nueva-clave-123', $user->refresh()->password))->toBeTrue();
});

test('administrators can deactivate and reactivate a user', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();

    $component = Livewire::test(Index::class)
        ->call('confirmDeactivation', $user->id)
        ->call('deactivate');

    expect($user->refresh()->isDeactivated())->toBeTrue();

    $component->call('reactivate', $user->id);

    expect($user->refresh()->isDeactivated())->toBeFalse();
});

test('administrators cannot deactivate themselves', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('confirmDeactivation', $admin->id)
        ->assertForbidden();

    expect($admin->refresh()->isDeactivated())->toBeFalse();
});
