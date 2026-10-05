<?php

test('every page tells the browser not to guess types, frame it elsewhere or leak addresses', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Strict-Transport-Security');
});

test('over https the browser is told to stick to https', function () {
    $this->get('https://localhost/login')
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});
