<?php

use App\Enums\Role;
use App\Models\City;
use App\Models\School;
use App\Models\User;

test('only administrators can manage municipalities and schools', function (Role $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create();
    $school = School::factory()->create();

    expect($user->can('viewAny', City::class))->toBe($allowed)
        ->and($user->can('create', City::class))->toBe($allowed)
        ->and($user->can('update', $school->city))->toBe($allowed)
        ->and($user->can('delete', $school->city))->toBe($allowed)
        ->and($user->can('create', School::class))->toBe($allowed)
        ->and($user->can('update', $school))->toBe($allowed)
        ->and($user->can('delete', $school))->toBe($allowed);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, false],
    'projector' => [Role::Projector, false],
]);
