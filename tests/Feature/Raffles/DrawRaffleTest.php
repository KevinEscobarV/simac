<?php

use App\Actions\Raffles\DrawRaffle;
use App\Enums\ProjectionPhase;
use App\Enums\QuorumType;
use App\Enums\RaffleAnimation;
use App\Events\ProjectionUpdated;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Models\User;
use App\Support\RaffleFilters;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

function drawRaffle(RaffleFilters $filters = new RaffleFilters, int $winners = 1): Raffle
{
    return app(DrawRaffle::class)->handle(User::factory()->admin()->create(), $filters, 'Bicicleta', $winners, RaffleAnimation::Wheel);
}

test('the winners are drawn among the participants and the record keeps everyone who took part', function () {
    $admin = User::factory()->admin()->create();
    $teachers = Teacher::factory()->count(6)->create();

    $raffle = app(DrawRaffle::class)->handle($admin, new RaffleFilters, 'Televisor de 50 pulgadas', 3, RaffleAnimation::Drum);
    $winners = $raffle->winners;

    expect($raffle->participants()->pluck('teachers.id')->sort()->values()->all())->toBe($teachers->pluck('id')->all())
        ->and($raffle->participants_count)->toBe(6)
        ->and($winners->pluck('pivot.winner_position')->all())->toBe([1, 2, 3])
        ->and($winners->pluck('id')->diff($teachers->pluck('id')))->toBeEmpty()
        ->and($raffle->prize)->toBe('Televisor de 50 pulgadas')
        ->and($raffle->winners_count)->toBe(3)
        ->and($raffle->animation)->toBe(RaffleAnimation::Drum)
        ->and($raffle->filter_description)->toBe(__('All teachers'))
        ->and($raffle->drawer->is($admin))->toBeTrue();
});

test('the record keeps the assembly in progress and whether it had quorum, even if the quorum changes later', function () {
    $assembly = Assembly::factory()->withQuorum(QuorumType::Count, 2)->create();
    Attendance::factory()->for($assembly)->count(2)->create();

    $raffle = drawRaffle();
    $assembly->update(['quorum_value' => 10]);

    expect($raffle->refresh()->assembly->is($assembly))->toBeTrue()
        ->and($raffle->quorum_met)->toBeTrue();
});

test('the record says the quorum was not met, or nothing when there was none to meet', function () {
    Teacher::factory()->count(2)->create();

    $withoutAssembly = drawRaffle();
    Projection::current()->release();

    Assembly::factory()->withQuorum(QuorumType::Count, 5)->create();
    $short = drawRaffle();

    expect($withoutAssembly->assembly_id)->toBeNull()
        ->and($withoutAssembly->quorum_met)->toBeNull()
        ->and($short->quorum_met)->toBeFalse();
});

test('drawing among those present needs an open assembly', function () {
    Teacher::factory()->count(3)->create();

    expect(fn () => drawRaffle(new RaffleFilters(presentOnly: true)))->toThrow(ValidationException::class);

    expect(Raffle::count())->toBe(0);
});

test('there must be at least two participants and more participants than winners', function (int $teachers, int $winners) {
    Teacher::factory()->count($teachers)->create();

    expect(fn () => drawRaffle(winners: $winners))->toThrow(ValidationException::class);

    expect(Raffle::count())->toBe(0);
})->with([
    'a single participant' => [1, 1],
    'as many winners as participants' => [3, 3],
]);

test('earlier winners of the same assembly are left out, unless asked otherwise', function () {
    Assembly::factory()->create();
    Teacher::factory()->count(3)->create();

    $first = drawRaffle();
    Projection::current()->release();
    $second = drawRaffle();
    Projection::current()->release();
    $third = drawRaffle(new RaffleFilters(excludePreviousWinners: false));

    expect($second->participants->pluck('id'))->not->toContain($first->winners->sole()->id)
        ->and($second->participants_count)->toBe(2)
        ->and($third->participants_count)->toBe(3);
});

test('a drawn raffle is loaded on the screens, waiting for the go', function () {
    Event::fake([ProjectionUpdated::class]);
    Teacher::factory()->count(2)->create();

    $raffle = drawRaffle();
    $projection = Projection::current();

    expect($projection->phase)->toBe(ProjectionPhase::Ready)
        ->and($projection->raffle->is($raffle))->toBeTrue()
        ->and($projection->winner_position)->toBe(1)
        ->and($projection->attempt)->toBe(0);

    Event::assertDispatched(ProjectionUpdated::class, fn (ProjectionUpdated $event) => $event->raffleId === $raffle->id);
});

test('another raffle cannot be drawn while one is on screen', function () {
    Teacher::factory()->count(2)->create();
    drawRaffle();

    expect(fn () => drawRaffle())->toThrow(ValidationException::class);

    expect(Raffle::count())->toBe(1);
});
