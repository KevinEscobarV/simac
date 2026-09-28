<?php

use App\Actions\Raffles\DrawRaffle;
use App\Enums\ProjectionPhase;
use App\Enums\RaffleAnimation;
use App\Enums\Role;
use App\Livewire\Screen\Index;
use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Models\User;
use App\Support\RaffleFilters;
use Livewire\Livewire;

function screenRaffle(int $winners = 1, int $participants = 4, RaffleAnimation $animation = RaffleAnimation::Wheel): Raffle
{
    Teacher::factory()->count($participants)->create();

    return app(DrawRaffle::class)->handle(User::factory()->admin()->create(), new RaffleFilters, 'Bicicleta todoterreno', $winners, $animation);
}

test('guests are redirected to the login page', function () {
    $this->get(route('screen'))->assertRedirect(route('login'));
});

test('the projector and administrators see the screen, the registrar does not', function (Role $role, bool $allowed) {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('screen'))->assertStatus($allowed ? 200 : 403);
})->with([
    'projector' => [Role::Projector, true],
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, false],
]);

test('at rest the screen waits, with the assembly in progress', function () {
    $this->actingAs(User::factory()->projector()->create());
    Assembly::factory()->create(['name' => 'Asamblea General Ordinaria']);

    Livewire::test(Index::class)
        ->assertSee(__('Waiting for the raffle'))
        ->assertSee('Asamblea General Ordinaria');
});

test('a loaded raffle announces itself without giving away the winner', function () {
    $this->actingAs(User::factory()->projector()->create());
    $raffle = screenRaffle();

    $screen = Livewire::test(Index::class)
        ->assertSee(__('GET READY'))
        ->assertSee('Bicicleta todoterreno')
        ->assertSee(trans_choice('{1} participant|[2,*] participants', 4));

    expect($screen->instance()->currentWinner)->toBeNull()
        ->and($screen->instance()->reel)->toBe([]);

    $screen->assertDontSee($raffle->winners->sole()->name);
});

test('the go brings the winner into the animation, among the others still in the draw', function () {
    $this->actingAs(User::factory()->projector()->create());
    $raffle = screenRaffle(winners: 2, participants: 5);
    $projection = Projection::current();
    $projection->launch();
    $projection->finish(1);
    $projection->launch();

    [$first, $second] = $raffle->winners->all();
    $reel = collect(Livewire::test(Index::class)->instance()->reel);

    expect($reel->pluck('id'))->toContain($second->id)
        ->not->toContain($first->id)
        ->and($reel)->toHaveCount(4);
});

test('every screen shows the same wheel', function () {
    $this->actingAs(User::factory()->projector()->create());
    screenRaffle(participants: 12);
    Projection::current()->launch();

    expect(Livewire::test(Index::class)->instance()->reel)
        ->toBe(Livewire::test(Index::class)->instance()->reel);
});

test('the animation reaching the end makes the winner public, and an old attempt does not', function () {
    $this->actingAs(User::factory()->projector()->create());
    $raffle = screenRaffle();
    $projection = Projection::current();
    $projection->launch();
    $projection->repeat();

    $screen = Livewire::test(Index::class)->call('finish', 1);
    expect(Projection::current()->phase)->toBe(ProjectionPhase::Animating);

    $screen->call('finish', 2)
        ->assertSee(__('Congratulations!'))
        ->assertSee($raffle->winners->sole()->name);

    expect(Projection::current()->phase)->toBe(ProjectionPhase::Winner);
});

test('with several winners the raffle ends with the roll of honor', function () {
    $this->actingAs(User::factory()->projector()->create());
    $raffle = screenRaffle(winners: 2, participants: 5, animation: RaffleAnimation::Drum);
    $projection = Projection::current();
    $projection->launch();
    $projection->finish(1);
    $projection->launch();
    $projection->finish(2);

    Livewire::test(Index::class)
        ->assertSee(__('Roll of honor'))
        ->assertSeeInOrder($raffle->winners->pluck('name')->all());
});

test('the screen reports that it is on', function () {
    $this->actingAs(User::factory()->projector()->create());

    Livewire::test(Index::class)->call('beat', 'screen-in-the-hall');

    expect(Projection::connectedScreens())->toBe(1);
});
