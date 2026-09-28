<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\City;
use App\Models\User;

class CityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }

    public function update(User $user, City $city): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }

    /**
     * Whether a city still has schools is a business rule, not a permission:
     * DeleteCity enforces it and tells the user what is left.
     */
    public function delete(User $user, City $city): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }
}
