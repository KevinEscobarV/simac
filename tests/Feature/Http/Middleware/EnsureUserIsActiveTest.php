<?php

use App\Models\User;

test('a deactivated user is signed out on their next request', function () {
    $this->actingAs(User::factory()->deactivated()->create());

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => __('This account has been deactivated. Ask an administrator to reactivate it.'),
        ]);

    $this->assertGuest();
});

test('a deactivated user who signs in with the right password ends up signed out', function () {
    $user = User::factory()->deactivated()->create();

    $this->followingRedirects()
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertSee(__('This account has been deactivated. Ask an administrator to reactivate it.'));

    $this->assertGuest();
});

test('active users are let through', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk();

    $this->assertAuthenticated();
});
