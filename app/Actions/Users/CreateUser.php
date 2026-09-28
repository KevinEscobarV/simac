<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    /**
     * Create a user together with their single role.
     */
    public function handle(string $name, string $email, string $password, Role $role): User
    {
        return DB::transaction(function () use ($name, $email, $password, $role): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $user->assignRole($role);

            return $user;
        });
    }
}
