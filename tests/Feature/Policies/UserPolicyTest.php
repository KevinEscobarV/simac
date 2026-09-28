<?php

use App\Enums\Role;
use App\Models\User;

test('only administrators can list, create and edit users', function (Role $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create();
    $other = User::factory()->create();

    expect($user->can('viewAny', User::class))->toBe($allowed)
        ->and($user->can('create', User::class))->toBe($allowed)
        ->and($user->can('update', $other))->toBe($allowed);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, false],
    'projector' => [Role::Projector, false],
]);

test('administrators can change the role of other users', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('changeRole', User::factory()->create()))->toBeTrue();
});

test('nobody can change their own role', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('changeRole', $admin))->toBeFalse();
});

test('administrators can deactivate other active users', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('deactivate', User::factory()->create()))->toBeTrue();
});

test('nobody can deactivate themselves', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('deactivate', $admin))->toBeFalse();
});

test('a deactivated user cannot be deactivated again', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('deactivate', User::factory()->deactivated()->create()))->toBeFalse();
});

test('only deactivated users can be reactivated', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('reactivate', User::factory()->deactivated()->create()))->toBeTrue()
        ->and($admin->can('reactivate', User::factory()->create()))->toBeFalse();
});

test('registrars and projectors cannot deactivate, reactivate or change roles', function (Role $role) {
    $user = User::factory()->withRole($role)->create();

    expect($user->can('deactivate', User::factory()->create()))->toBeFalse()
        ->and($user->can('reactivate', User::factory()->deactivated()->create()))->toBeFalse()
        ->and($user->can('changeRole', User::factory()->create()))->toBeFalse();
})->with([
    'registrar' => [Role::Registrar],
    'projector' => [Role::Projector],
]);
