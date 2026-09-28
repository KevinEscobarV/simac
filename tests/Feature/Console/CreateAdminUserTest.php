<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('creates an administrator from the console', function () {
    $this->artisan('app:create-admin-user')
        ->expectsQuestion(__('Name'), 'Martha Guatibonza')
        ->expectsQuestion(__('Email address'), 'martha@simac.test')
        ->expectsQuestion(__('Password'), 'secreto-largo-123')
        ->expectsQuestion(__('Confirm password'), 'secreto-largo-123')
        ->assertSuccessful();

    $admin = User::firstWhere('email', 'martha@simac.test');

    expect($admin->name)->toBe('Martha Guatibonza')
        ->and($admin->assignedRole())->toBe(Role::Admin)
        ->and(Hash::check('secreto-largo-123', $admin->password))->toBeTrue();
});

test('rejects a password confirmation that does not match', function () {
    $this->artisan('app:create-admin-user')
        ->expectsQuestion(__('Name'), 'Martha Guatibonza')
        ->expectsQuestion(__('Email address'), 'martha@simac.test')
        ->expectsQuestion(__('Password'), 'secreto-largo-123')
        ->expectsQuestion(__('Confirm password'), 'otra-cosa')
        ->expectsOutputToContain(__('The passwords do not match.'))
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'martha@simac.test']);
});
