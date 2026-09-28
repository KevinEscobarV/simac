<?php

namespace App\Console\Commands;

use App\Actions\Users\CreateUser;
use App\Enums\Role;
use App\Models\User;
use Closure;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Bootstraps a new installation: there is no public registration, so the
 * first administrator has to be created from the console. The password is
 * asked interactively so it never ends up in .env or the shell history.
 */
#[Signature('app:create-admin-user')]
#[Description('Create an administrator account')]
class CreateAdminUser extends Command
{
    public function handle(CreateUser $createUser): int
    {
        $this->callSilently('db:seed', [
            '--class' => RolesAndPermissionsSeeder::class,
            '--force' => true,
        ]);

        $name = text(
            label: __('Name'),
            required: true,
            validate: ['name' => ['required', 'string', 'max:255']],
        );

        $email = text(
            label: __('Email address'),
            required: true,
            validate: ['email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)]],
        );

        $password = password(
            label: __('Password'),
            required: true,
            validate: ['password' => ['required', 'string', Password::default()]],
        );

        password(
            label: __('Confirm password'),
            required: true,
            validate: ['password_confirmation' => [
                'required',
                function (string $attribute, string $confirmation, Closure $fail) use ($password): void {
                    if ($confirmation !== $password) {
                        $fail('The passwords do not match.')->translate();
                    }
                },
            ]],
        );

        $user = $createUser->handle($name, $email, $password, Role::Admin);

        $this->components->info(__('Administrator :email created.', ['email' => $user->email]));

        return self::SUCCESS;
    }
}
