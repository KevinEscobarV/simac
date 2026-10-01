<?php

use App\Events\ProjectionUpdated;
use App\Models\Projection;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('an announcement leaves out the browser that caused it', function () {
    $this->actingAs(User::factory()->projector()->create());
    Projection::recordScreen('screen-in-the-hall');
    Event::fake([ProjectionUpdated::class]);

    $this->withHeader('X-Socket-ID', '1234.5678')
        ->post(route('screen.leave'), ['screen' => 'screen-in-the-hall'])
        ->assertNoContent();

    Event::assertDispatched(ProjectionUpdated::class, fn (ProjectionUpdated $event) => $event->socket === '1234.5678');
});

test('a browser not yet connected does not keep the announcement from anyone', function () {
    $this->actingAs(User::factory()->projector()->create());
    Projection::recordScreen('screen-in-the-hall');
    Event::fake([ProjectionUpdated::class]);

    $this->withHeader('X-Socket-ID', 'undefined')
        ->post(route('screen.leave'), ['screen' => 'screen-in-the-hall'])
        ->assertNoContent();

    Event::assertDispatched(ProjectionUpdated::class, fn (ProjectionUpdated $event) => $event->socket === null);
});
