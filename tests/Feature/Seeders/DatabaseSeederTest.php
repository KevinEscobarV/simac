<?php

use App\Enums\Role;
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
