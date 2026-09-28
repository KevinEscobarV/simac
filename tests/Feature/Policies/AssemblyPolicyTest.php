<?php

use App\Enums\Role;
use App\Models\Assembly;
use App\Models\User;

test('only administrators manage assemblies', function (Role $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create();
    $open = Assembly::factory()->create();
    $closed = Assembly::factory()->closed()->create();

    expect($user->can('viewAny', Assembly::class))->toBe($allowed)
        ->and($user->can('create', Assembly::class))->toBe($allowed)
        ->and($user->can('update', $open))->toBe($allowed)
        ->and($user->can('close', $open))->toBe($allowed)
        ->and($user->can('reopen', $closed))->toBe($allowed)
        ->and($user->can('delete', $closed))->toBe($allowed);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, false],
    'projector' => [Role::Projector, false],
]);

test('administrators and registrars register attendance, only on an open assembly', function (Role $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create();

    expect($user->can('registerAttendance', Assembly::factory()->create()))->toBe($allowed)
        ->and($user->can('registerAttendance', Assembly::factory()->closed()->create()))->toBeFalse();
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, true],
    'projector' => [Role::Projector, false],
]);

test('an open assembly cannot be reopened and a closed one cannot be closed', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('reopen', Assembly::factory()->create()))->toBeFalse()
        ->and($admin->can('close', Assembly::factory()->closed()->create()))->toBeFalse();
});
