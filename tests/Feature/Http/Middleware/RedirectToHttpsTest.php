<?php

test('when the app is served over https, a plain http request goes to the same page over https', function () {
    config(['app.url' => 'https://simac.test']);

    $this->get('http://simac.test/login?from=search')
        ->assertStatus(301)
        ->assertRedirect('https://simac.test/login?from=search');
});

test('a request over https goes through', function () {
    config(['app.url' => 'https://simac.test']);

    $this->get('https://simac.test/login')->assertOk();
});

test('in development over http nothing is redirected', function () {
    config(['app.url' => 'http://localhost:8000']);

    $this->get('http://localhost:8000/login')->assertOk();
});
