<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }

    public function update(User $user, School $school): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }

    public function delete(User $user, School $school): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }
}
