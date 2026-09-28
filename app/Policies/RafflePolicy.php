<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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

    /**
     * The record in PDF names every winner, so it waits until the screens
     * have shown them all.
     */
    public function download(User $user, Raffle $raffle): Response
    {
        if (! $user->hasPermissionTo(Permission::ViewRaffles)) {
            return Response::deny();
        }

        return $raffle->isPublic(Projection::current())
            ? Response::allow()
            : Response::deny(__('The record can be downloaded once the screen has shown every winner.'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::DrawRaffles);
    }
}
