<?php

use App\Enums\ProjectionPhase;
use App\Events\ProjectionUpdated;
use App\Models\Projection;
use App\Models\Raffle;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

/**
 * A raffle loaded on the screens, with one screen on to show it.
 */
function loadedProjection(int $winners = 1): Projection
{
    Projection::recordScreen('hall');

    $projection = Projection::current();
    $projection->prepare(Raffle::factory()->create(['winners_count' => $winners]));

    return $projection;
}

test('the projection starts at rest', function () {
    $projection = Projection::current();

    expect($projection->phase)->toBe(ProjectionPhase::Idle)
        ->and($projection->raffle_id)->toBeNull()
        ->and(Projection::count())->toBe(1);
});

test('go animates the first winner, and each go after a winner animates the next one', function () {
    $projection = loadedProjection(winners: 2);

    $projection->launch();
    expect($projection->only('attempt', 'winner_position'))->toBe(['attempt' => 1, 'winner_position' => 1])
        ->and($projection->phase)->toBe(ProjectionPhase::Animating);

    expect($projection->finish(1))->toBeTrue()
        ->and($projection->phase)->toBe(ProjectionPhase::Winner);

    $projection->launch();
    expect($projection->only('attempt', 'winner_position'))->toBe(['attempt' => 2, 'winner_position' => 2]);

    $projection->finish(2);

    expect(fn () => $projection->launch())->toThrow(ValidationException::class);
    expect($projection->fresh()->phase)->toBe(ProjectionPhase::Winner);
});

test('repeating shows the same winner again from the start', function () {
    $projection = loadedProjection();
    $projection->launch();
    $projection->finish(1);

    $projection->repeat();

    expect($projection->phase)->toBe(ProjectionPhase::Animating)
        ->and($projection->only('attempt', 'winner_position'))->toBe(['attempt' => 2, 'winner_position' => 1]);
});

test('a screen reporting an animation that was already replaced changes nothing', function () {
    $projection = loadedProjection();
    $projection->launch();
    $projection->repeat();

    expect($projection->finish(1))->toBeFalse()
        ->and($projection->fresh()->phase)->toBe(ProjectionPhase::Animating)
        ->and($projection->finish(2))->toBeTrue();
});

test('nothing is launched or repeated without a raffle, and nothing is repeated before the go', function () {
    $idle = Projection::current();

    expect(fn () => $idle->launch())->toThrow(ValidationException::class)
        ->and(fn () => $idle->repeat())->toThrow(ValidationException::class)
        ->and(fn () => loadedProjection()->repeat())->toThrow(ValidationException::class);
});

test('the go and the repeat wait for a screen to be on', function () {
    $projection = loadedProjection(winners: 2);
    Projection::forgetScreen('hall');

    expect(fn () => $projection->launch())->toThrow(ValidationException::class, __('No screen is connected. Open the projection screen, or reload it, to launch.'))
        ->and($projection->fresh()->phase)->toBe(ProjectionPhase::Ready);

    Projection::recordScreen('hall');
    $projection->launch();
    Projection::forgetScreen('hall');

    expect(fn () => $projection->repeat())->toThrow(ValidationException::class)
        ->and($projection->fresh()->attempt)->toBe(1);
});

test('releasing sends the screens back to rest and keeps the record', function () {
    $projection = loadedProjection();
    $raffle = $projection->raffle;
    $projection->launch();

    $projection->release();

    expect($projection->fresh()->phase)->toBe(ProjectionPhase::Idle)
        ->and($projection->raffle_id)->toBeNull()
        ->and($projection->attempt)->toBe(0);

    $this->assertModelExists($raffle);
});

test('every change is announced to the screens, and a report that changes nothing is not', function () {
    Projection::recordScreen('hall');
    Event::fake([ProjectionUpdated::class]);
    $projection = loadedProjection();

    $projection->launch();
    $projection->finish(7);

    Event::assertDispatchedTimes(ProjectionUpdated::class, 2);
    Event::assertDispatched(ProjectionUpdated::class, fn (ProjectionUpdated $event) => $event->phase === ProjectionPhase::Animating && $event->attempt === 1);
});

test('screens count while they keep reporting, and a new one is announced', function () {
    Event::fake([ProjectionUpdated::class]);

    Projection::recordScreen('screen-a');
    Projection::recordScreen('screen-b');
    Projection::recordScreen('screen-a');

    expect(Projection::connectedScreens())->toBe(2);
    Event::assertDispatchedTimes(ProjectionUpdated::class, 2);

    $this->travel(Projection::SCREEN_TIMEOUT - 10)->seconds();
    Projection::recordScreen('screen-b');
    $this->travel(20)->seconds();

    expect(Projection::connectedScreens())->toBe(1);
});

test('a screen that closes stops counting at once, and that is announced', function () {
    Projection::recordScreen('screen-a');
    Projection::recordScreen('screen-b');
    Event::fake([ProjectionUpdated::class]);

    Projection::forgetScreen('screen-a');
    Projection::forgetScreen('screen-a');

    expect(Projection::connectedScreens())->toBe(1);
    Event::assertDispatchedTimes(ProjectionUpdated::class, 1);
});
