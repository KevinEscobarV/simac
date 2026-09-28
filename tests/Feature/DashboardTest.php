<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('registrars land on the registration desk', function () {
    $this->actingAs(User::factory()->registrar()->create());

    $this->get(route('dashboard'))->assertRedirect(route('desk'));
});

test('administrators stay on the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))->assertOk();
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
