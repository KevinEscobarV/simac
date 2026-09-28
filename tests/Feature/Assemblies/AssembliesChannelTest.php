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

test('the panel and the desks follow the assemblies live, the projector does not', function (Role $role, bool $allowed) {
    $this->actingAs(User::factory()->withRole($role)->create())
        ->post('/broadcasting/auth', ['channel_name' => 'private-assemblies', 'socket_id' => '1234.5678'])
        ->assertStatus($allowed ? 200 : 403);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, true],
    'projector' => [Role::Projector, false],
]);

test('guests cannot follow the assemblies live', function () {
    $this->post('/broadcasting/auth', ['channel_name' => 'private-assemblies', 'socket_id' => '1234.5678'])
        ->assertForbidden();
});
