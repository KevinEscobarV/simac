<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Raffle;
use App\Models\User;

class RafflePolicy
{
    /**
     * The history of raffles and their records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ViewRaffles);
    }

    public function view(User $user, Raffle $raffle): bool
    {
        return $user->hasPermissionTo(Permission::ViewRaffles);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::DrawRaffles);
    }
}
