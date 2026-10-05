<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * The settings are a single row, so their abilities take no model.
 */
class SettingPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageSettings);
    }
}
