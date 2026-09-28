<?php

use App\Livewire\Raffles\Index;
use App\Livewire\Raffles\Show;
use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('raffles.index'))->assertRedirect(route('login'));
});

test('only administrators read the history and its records', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());
    $raffle = Raffle::factory()->create();

    $this->get(route('raffles.index'))->assertForbidden();
    $this->get(route('raffles.show', $raffle))->assertForbidden();
})->with(['registrar', 'projector']);

test('administrators open the history and a record', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()->drawnAmong(Teacher::factory()->count(3)->create())->create();

    $this->get(route('raffles.index'))->assertOk();
    $this->get(route('raffles.show', $raffle))->assertOk();
});

test('the history lists the records newest first', function () {
    $this->actingAs(User::factory()->admin()->create());

    foreach (['Bicicleta', 'Tableta', 'Mercado familiar'] as $minutesAgo => $prize) {
        Raffle::factory()->drawnAmong(Teacher::factory()->count(3)->create())->create([
            'prize' => $prize,
            'drawn_at' => now()->subMinutes(10 - $minutesAgo),
        ]);
    }

    Livewire::test(Index::class)
        ->assertSee(__('Latest raffle'))
        ->assertSeeInOrder(['Mercado familiar', 'Tableta', 'Bicicleta']);
});

test('the history filters by assembly', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->closed()->create();
    Raffle::factory()->for($assembly)->create(['prize' => 'Bicicleta']);
    Raffle::factory()->create(['prize' => 'Tableta']);

    Livewire::test(Index::class)
        ->set('assembly', (string) $assembly->id)
        ->assertSee('Bicicleta')
        ->assertDontSee('Tableta')
        ->set('assembly', Index::WITHOUT_ASSEMBLY)
        ->assertSee('Tableta')
        ->assertDontSee('Bicicleta');
});

test('only a record drawn without quorum is marked', function (?bool $quorumMet, bool $marked) {
    $this->actingAs(User::factory()->admin()->create());
    Raffle::factory()->for(Assembly::factory()->closed())->create(['quorum_met' => $quorumMet]);

    Livewire::test(Index::class)->{$marked ? 'assertSee' : 'assertDontSee'}(__('No quorum'));
})->with([
    'without quorum' => [false, true],
    'with quorum' => [true, false],
    'no quorum required' => [null, false],
]);

test('the history does not give away a winner before the screen reveals it', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()->drawnAmong(Teacher::factory()->count(4)->create())->create(['winners_count' => 2]);
    [$first, $second] = $raffle->winners->all();
    Projection::recordScreen('hall');
    $projection = Projection::current();
    $projection->prepare($raffle);

    $history = Livewire::test(Index::class)
        ->assertSee(__('On screen'))
        ->assertDontSee($first->name)
        ->assertDontSee($second->name);

    $projection->launch();
    $projection->finish(1);

    $history->call('refreshLive')
        ->assertSee($first->name)
        ->assertDontSee($second->name);
});

test('a record shows its winners in order, its data and every participant', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()
        ->for(Assembly::factory()->closed()->create(['name' => 'Asamblea de Delegados']))
        ->drawnAmong(Teacher::factory()->count(5)->create())
        ->create([
            'prize' => 'Bicicleta todoterreno',
            'winners_count' => 2,
            'filter_description' => 'Solo afiliados',
            'drawn_by' => User::factory()->admin()->create(['name' => 'Rocío Galindo'])->id,
        ]);

    Livewire::test(Show::class, ['raffle' => $raffle])
        ->assertSee(['Bicicleta todoterreno', 'Asamblea de Delegados', 'Solo afiliados', 'Rocío Galindo'])
        ->assertSeeInOrder($raffle->winners->pluck('name')->all())
        ->assertSee($raffle->participants->pluck('name')->all());
});

test('the participants of a record can be searched', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()->drawnAmong(collect([
        Teacher::factory()->create(['name' => 'Yolanda Cristancho']),
        Teacher::factory()->create(['name' => 'Nelson Pidiache']),
    ]))->create();

    Livewire::test(Show::class, ['raffle' => $raffle])
        ->set('search', 'cristancho')
        ->assertSee('Yolanda Cristancho')
        ->assertDontSee('Nelson Pidiache');
});

test('a record on screen shows only the winners already revealed', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()->drawnAmong(Teacher::factory()->count(4)->create())->create(['winners_count' => 2]);
    Projection::recordScreen('hall');
    $projection = Projection::current();
    $projection->prepare($raffle);
    $projection->launch();
    $projection->finish(1);

    $record = Livewire::test(Show::class, ['raffle' => $raffle])
        ->assertSee(__('Winner :position', ['position' => 1]))
        ->assertDontSee(__('Winner :position', ['position' => 2]))
        ->assertSee(__('To be revealed'));

    $projection->launch();
    $projection->finish(2);

    $record->call('refreshLive')
        ->assertSee(__('Winner :position', ['position' => 2]))
        ->assertDontSee(__('To be revealed'));
});
