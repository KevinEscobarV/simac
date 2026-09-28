<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionName;
use App\Enums\Role as RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs roles and permissions with the Role and Permission enums. Idempotent:
 * run it on every deploy so the database always matches the code.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(PermissionRegistrar $registrar): void
    {
        $registrar->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        // Spatie flushes its cache on model events, which callers such as
        // DatabaseSeeder mute with WithoutModelEvents: flush it explicitly so
        // the roles below can find the permissions just created.
        $registrar->forgetCachedPermissions();

        foreach (RoleName::cases() as $role) {
            Role::findOrCreate($role->value)->syncPermissions(
                array_map(fn (PermissionName $permission) => $permission->value, $role->permissions()),
            );
        }

        $registrar->forgetCachedPermissions();
    }
}
