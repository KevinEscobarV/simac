<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageUsers);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageUsers);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo(Permission::ManageUsers);
    }

    /**
     * Nobody changes their own role: the last administrator could otherwise
     * lock everyone out of the panel.
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->hasPermissionTo(Permission::ManageUsers) && $user->isNot($model);
    }

    /**
     * Nobody deactivates themselves, for the same reason.
     */
    public function deactivate(User $user, User $model): bool
    {
        return $user->hasPermissionTo(Permission::ManageUsers)
            && $user->isNot($model)
            && ! $model->isDeactivated();
    }

    public function reactivate(User $user, User $model): bool
    {
        return $user->hasPermissionTo(Permission::ManageUsers) && $model->isDeactivated();
    }
}
