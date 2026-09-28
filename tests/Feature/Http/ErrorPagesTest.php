<?php

use App\Models\User;

test('a page that does not exist says so in Spanish, with the way home', function () {
    $this->get('/no-existe')
        ->assertNotFound()
        ->assertSee(__('Not Found'))
        ->assertSee(__('The page you are looking for does not exist or was moved.'))
        ->assertSee(route('home'));
});

test('a forbidden page explains it in Spanish instead of the framework message', function () {
    $this->actingAs(User::factory()->registrar()->create());

    $this->get(route('teachers.index'))
        ->assertForbidden()
        ->assertSee(__('Forbidden'))
        ->assertSee(__('This action is unauthorized.'))
        ->assertDontSee('This action is unauthorized.');
});
