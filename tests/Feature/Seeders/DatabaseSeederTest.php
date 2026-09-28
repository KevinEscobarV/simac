<?php

use App\Enums\Role;
use App\Models\Assembly;
use App\Models\City;
use App\Models\Projection;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;

test('seeds one user per role on a database without roles or permissions', function () {
    RoleModel::query()->delete();
    Permission::query()->delete();

    $this->seed(DatabaseSeeder::class);

    expect(User::firstWhere('email', 'admin@simac.test')->assignedRole())->toBe(Role::Admin)
        ->and(User::firstWhere('email', 'registro@simac.test')->assignedRole())->toBe(Role::Registrar)
        ->and(User::firstWhere('email', 'pantalla@simac.test')->assignedRole())->toBe(Role::Projector);
});

test('seeds the roll of the demo', function () {
    $this->seed(DatabaseSeeder::class);

    expect(City::count())->toBe(19)
        ->and(School::count())->toBe(8)
        ->and(Teacher::count())->toBe(18)
        ->and(Teacher::firstWhere('document_number', '1118920467')->school->name)->toBe('IE La Presentación')
        ->and(Assembly::current()?->opener?->email)->toBe('admin@simac.test');
});

test('seeds a past assembly with its raffles and leaves the open one and the screen untouched', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Assembly::query()->closed()->sole()->raffles()->count())->toBe(3)
        ->and(Assembly::current()->raffles()->exists())->toBeFalse()
        ->and(Projection::current()->isLive())->toBeFalse();
});
