<?php

use App\Enums\Role;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\User;

test('only administrators draw raffles, drive the projection and read the records', function (Role $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create();

    expect($user->can('create', Raffle::class))->toBe($allowed)
        ->and($user->can('viewAny', Raffle::class))->toBe($allowed)
        ->and($user->can('view', Raffle::factory()->create()))->toBe($allowed)
        ->and($user->can('control', Projection::class))->toBe($allowed);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, false],
    'projector' => [Role::Projector, false],
]);

test('the projector watches the projection without driving it', function () {
    $projector = User::factory()->projector()->create();

    expect($projector->can('watch', Projection::class))->toBeTrue()
        ->and($projector->can('control', Projection::class))->toBeFalse()
        ->and(User::factory()->registrar()->create()->can('watch', Projection::class))->toBeFalse();
});
