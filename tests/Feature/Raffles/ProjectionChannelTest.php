<?php

use App\Enums\Role;
use App\Models\User;

beforeEach(function () {
    // Tests broadcast nowhere: the channels are registered again on a Reverb
    // broadcaster so the real subscription check runs, without a server.
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);

    require base_path('routes/channels.php');
});

test('the screens and the console follow the projection, the registrar does not', function (Role $role, bool $allowed) {
    $this->actingAs(User::factory()->withRole($role)->create())
        ->post('/broadcasting/auth', ['channel_name' => 'private-projection', 'socket_id' => '1234.5678'])
        ->assertStatus($allowed ? 200 : 403);
})->with([
    'administrator' => [Role::Admin, true],
    'projector' => [Role::Projector, true],
    'registrar' => [Role::Registrar, false],
]);
