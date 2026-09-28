<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    /**
     * Update a user's details and replace their role.
     */
    public function handle(User $user, string $name, string $email, Role $role): User
    {
        DB::transaction(function () use ($user, $name, $email, $role): void {
            $user->update([
                'name' => $name,
                'email' => $email,
            ]);

            $user->syncRoles($role);
        });

        return $user;
    }
}
