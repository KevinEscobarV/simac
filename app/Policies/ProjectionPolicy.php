<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class ProjectionPolicy
{
    /**
     * Launching, repeating and releasing what the screens show: whoever
     * draws the raffles.
     */
    public function control(User $user): bool
    {
        return $user->hasPermissionTo(Permission::DrawRaffles);
    }

    /**
     * Following the projection: the screens and the console that drives them.
     */
    public function watch(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ViewScreen)
            || $user->hasPermissionTo(Permission::DrawRaffles);
    }
}
