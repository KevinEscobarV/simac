<?php

use App\Enums\Role;
use App\Models\Teacher;
use App\Models\User;

test('only administrators can manage the roll of teachers', function (Role $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create();
    $teacher = Teacher::factory()->create();
    $retired = Teacher::factory()->trashed()->create();

    expect($user->can('viewAny', Teacher::class))->toBe($allowed)
        ->and($user->can('create', Teacher::class))->toBe($allowed)
        ->and($user->can('update', $teacher))->toBe($allowed)
        ->and($user->can('delete', $teacher))->toBe($allowed)
        ->and($user->can('restore', $retired))->toBe($allowed);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, false],
    'projector' => [Role::Projector, false],
]);

test('a retired teacher can only be brought back', function () {
    $admin = User::factory()->admin()->create();
    $retired = Teacher::factory()->trashed()->create();

    expect($admin->can('update', $retired))->toBeFalse()
        ->and($admin->can('delete', $retired))->toBeFalse()
        ->and($admin->can('restore', $retired))->toBeTrue()
        ->and($admin->can('restore', Teacher::factory()->create()))->toBeFalse();
});
