<?php

use App\Actions\Raffles\DeclareWinnerAbsent;
use App\Actions\Raffles\DrawRaffle;
use App\Enums\ProjectionPhase;
use App\Enums\RaffleAnimation;
use App\Livewire\Raffles\Create;
use App\Livewire\Screen\Index as Screen;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Models\User;
use App\Support\RaffleFilters;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * A raffle among those present at the assembly, its first winner on screen.
 *
 * @param  Collection<int, Teacher>  $present
 */
function presentWinnerOnScreen(Assembly $assembly, Collection $present): Raffle
{
    $present->each(fn (Teacher $teacher) => Attendance::factory()->for($assembly)->for($teacher)->create());
    Projection::recordScreen('hall');

    $raffle = app(DrawRaffle::class)->handle(User::factory()->admin()->create(), new RaffleFilters(presentOnly: true), 'Bicicleta todoterreno', 1, RaffleAnimation::Wheel);

    $projection = Projection::current();
    $projection->launch();
    $projection->finish(1);

    return $raffle;
}

test('a winner who did not come forward stays in the record as absent, leaves the room and another is drawn for their position', function () {
    $admin = User::factory()->admin()->create();
    $assembly = Assembly::factory()->create();
    $raffle = presentWinnerOnScreen($assembly, Teacher::factory()->count(4)->create());
    $absent = $raffle->winners->sole();

    app(DeclareWinnerAbsent::class)->handle($admin);

    $replacement = $raffle->winners()->sole();
    $forfeit = $raffle->forfeits()->sole();

    expect($replacement->is($absent))->toBeFalse()
        ->and($replacement->pivot->winner_position)->toBe(1)
        ->and($forfeit->is($absent))->toBeTrue()
        ->and($forfeit->pivot->forfeited_position)->toBe(1)
        ->and($forfeit->pivot->forfeited_by)->toBe($admin->id)
        ->and($forfeit->pivot->forfeited_at)->not->toBeNull()
        ->and($assembly->attendances()->whereBelongsTo($absent)->sole()->checked_out_at)->not->toBeNull();

    expect(Projection::current())
        ->phase->toBe(ProjectionPhase::Animating)
        ->winner_position->toBe(1)
        ->attempt->toBe(2);
});

test('the replacement comes from the record, among those still in the room', function () {
    $assembly = Assembly::factory()->create();
    $raffle = presentWinnerOnScreen($assembly, Teacher::factory()->count(3)->create());
    [$gone, $stays] = $raffle->participants()->wherePivotNull('winner_position')->get()->all();

    $assembly->attendances()->whereBelongsTo($gone)->update(['checked_out_at' => now()]);
    Attendance::factory()->for($assembly)->create();

    app(DeclareWinnerAbsent::class)->handle(User::factory()->admin()->create());

    expect($raffle->winners()->sole()->is($stays))->toBeTrue();
});

test('with nobody left to take the prize, nothing changes', function () {
    $assembly = Assembly::factory()->create();
    $raffle = presentWinnerOnScreen($assembly, Teacher::factory()->count(2)->create());
    $winner = $raffle->winners->sole();
    $assembly->attendances()->whereKeyNot($assembly->attendances()->whereBelongsTo($winner)->value('id'))->update(['checked_out_at' => now()]);

    expect(fn () => app(DeclareWinnerAbsent::class)->handle(User::factory()->admin()->create()))
        ->toThrow(ValidationException::class);

    expect($raffle->winners()->sole()->is($winner))->toBeTrue()
        ->and($raffle->forfeits()->exists())->toBeFalse()
        ->and(Projection::current()->phase)->toBe(ProjectionPhase::Winner)
        ->and($assembly->attendances()->whereBelongsTo($winner)->sole()->checked_out_at)->toBeNull();
});

test('someone declared absent is never drawn again', function () {
    Teacher::factory()->count(2)->create();
    Projection::recordScreen('hall');
    $raffle = app(DrawRaffle::class)->handle(User::factory()->admin()->create(), new RaffleFilters, 'Bicicleta todoterreno', 1, RaffleAnimation::Wheel);
    $projection = Projection::current();
    $projection->launch();
    $projection->finish(1);

    app(DeclareWinnerAbsent::class)->handle(User::factory()->admin()->create());
    Projection::current()->finish(2);

    expect(fn () => app(DeclareWinnerAbsent::class)->handle(User::factory()->admin()->create()))
        ->toThrow(ValidationException::class);

    expect($raffle->forfeits()->count())->toBe(1);
});

test('only the winner on screen can be declared absent', function (Closure $phase) {
    Teacher::factory()->count(3)->create();
    Projection::recordScreen('hall');
    $raffle = app(DrawRaffle::class)->handle(User::factory()->admin()->create(), new RaffleFilters, 'Bicicleta todoterreno', 1, RaffleAnimation::Wheel);
    $winner = $raffle->winners->sole();
    $phase(Projection::current());

    expect(fn () => app(DeclareWinnerAbsent::class)->handle(User::factory()->admin()->create()))
        ->toThrow(ValidationException::class);

    expect($raffle->winners()->sole()->is($winner))->toBeTrue()
        ->and($raffle->forfeits()->exists())->toBeFalse();
})->with([
    'before the go' => fn () => fn (Projection $projection) => null,
    'during the animation' => fn () => fn (Projection $projection) => $projection->launch(),
]);

test('in a raffle among everyone, a winner who never checked in is only marked absent', function () {
    Teacher::factory()->count(3)->create();
    Projection::recordScreen('hall');
    $raffle = app(DrawRaffle::class)->handle(User::factory()->admin()->create(), new RaffleFilters, 'Bicicleta todoterreno', 1, RaffleAnimation::Wheel);
    $projection = Projection::current();
    $projection->launch();
    $projection->finish(1);

    app(DeclareWinnerAbsent::class)->handle(User::factory()->admin()->create());

    expect($raffle->forfeits()->count())->toBe(1)
        ->and($raffle->winners()->count())->toBe(1)
        ->and(Attendance::count())->toBe(0);
});

test('the console declares the winner on screen absent without giving away the replacement', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = presentWinnerOnScreen(Assembly::factory()->create(), Teacher::factory()->count(4)->create());
    $absent = $raffle->winners->sole();

    Livewire::test(Create::class)
        ->assertSee(__(':name did not come forward?', ['name' => $absent->name]))
        ->call('declareAbsent')
        ->assertSee($absent->name)
        ->assertSee(__('Did not come forward'))
        ->assertDontSee($raffle->winners()->sole()->name);

    expect($raffle->forfeits()->sole()->is($absent))->toBeTrue();
});

test('the animation of the replacement leaves out the winner who did not come forward', function () {
    $this->actingAs(User::factory()->projector()->create());
    $raffle = presentWinnerOnScreen(Assembly::factory()->create(), Teacher::factory()->count(6)->create());
    $absent = $raffle->winners->sole();

    app(DeclareWinnerAbsent::class)->handle(User::factory()->admin()->create());

    expect(collect(Livewire::test(Screen::class)->instance()->reel)->pluck('id'))
        ->toContain($raffle->winners()->sole()->id)
        ->not->toContain($absent->id);
});

test('the record page says who did not come forward, when and who declared it', function () {
    $admin = User::factory()->admin()->create(['name' => 'Rocío Galindo']);
    $this->actingAs($admin);
    $raffle = presentWinnerOnScreen(Assembly::factory()->create(), Teacher::factory()->count(4)->create());
    $absent = $raffle->winners->sole();

    app(DeclareWinnerAbsent::class)->handle($admin);
    Projection::current()->finish(2);

    $this->get(route('raffles.show', $raffle))
        ->assertOk()
        ->assertSee(__('Did not come forward'))
        ->assertSee($absent->name)
        ->assertSee(__('declared by :name', ['name' => 'Rocío Galindo']))
        ->assertSee($raffle->winners()->sole()->name);
});
