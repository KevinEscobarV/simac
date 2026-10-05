<?php

test('another name of the site goes to the same page on the app host', function () {
    config(['app.url' => 'https://simac.test']);

    $this->get('https://www.simac.test/login?from=bookmark')
        ->assertStatus(301)
        ->assertRedirect('https://simac.test/login?from=bookmark');
});

test('from http and another name it gets there in a single step', function () {
    config(['app.url' => 'https://simac.test']);

    $this->get('http://www.simac.test/login')
        ->assertStatus(301)
        ->assertRedirect('https://simac.test/login');
});

test('in development any host is served', function () {
    config(['app.url' => 'http://localhost:8000']);

    $this->get('http://127.0.0.1:8000/login')->assertOk();
});
