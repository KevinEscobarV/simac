<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('running the seeder again does not duplicate roles or permissions', function () {
    $roles = Role::count();
    $permissions = Permission::count();

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::count())->toBe($roles)
        ->and(Permission::count())->toBe($permissions);
});

test('the seeder restores permissions removed from a role', function () {
    Role::findByName('registrar')->syncPermissions([]);

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::findByName('registrar')->permissions->pluck('name')->all())
        ->toBe(['attendance.register']);
});
