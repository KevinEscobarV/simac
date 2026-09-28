<?php

use App\Livewire\Dashboard;
use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('registrars land on the registration desk', function () {
    $this->actingAs(User::factory()->registrar()->create());

    $this->get(route('dashboard'))->assertRedirect(route('desk'));
});

test('the projector lands on the projection screen', function () {
    $this->actingAs(User::factory()->projector()->create());

    $this->get(route('dashboard'))->assertRedirect(route('screen'));
});

test('administrators stay on the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))->assertOk();
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the home shows the assembly in progress, with the way to the desk', function () {
    $this->actingAs(User::factory()->admin()->create());
    Assembly::factory()->create(['name' => 'Asamblea de Delegados']);

    Livewire::test(Dashboard::class)
        ->assertSee(__('Assembly in progress'))
        ->assertSee('Asamblea de Delegados')
        ->assertSee(route('desk'));
});

test('without an open assembly the home offers to open one', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Dashboard::class)
        ->assertSee(__('No assembly open'))
        ->assertSee(__('Open assembly'));
});

test('a raffle on screen leads back to the console', function () {
    $this->actingAs(User::factory()->admin()->create());

    $home = Livewire::test(Dashboard::class)->assertDontSee(__('Go to the console'));

    Projection::current()->prepare(Raffle::factory()->create(['prize' => 'Bicicleta todoterreno']));

    $home->call('refreshLive')
        ->assertSee(__('Go to the console'))
        ->assertSee('Bicicleta todoterreno');
});

test('the home shows the numbers of the roll and the latest record', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(3)->create();
    Teacher::factory()->nonMember()->create();
    Raffle::factory()->drawnAmong(Teacher::query()->get())->create(['prize' => 'Mercado familiar']);

    Livewire::test(Dashboard::class)
        ->assertSee(__(':percent% of the roll', ['percent' => 75]))
        ->assertSee(__('Latest raffle'))
        ->assertSee('Mercado familiar');
});

test('someone with no post sees no shortcuts, numbers or records', function () {
    $this->actingAs(User::factory()->create());
    Assembly::factory()->create(['name' => 'Asamblea de Delegados']);

    Livewire::test(Dashboard::class)
        ->assertSee(__('Nothing to see here yet'))
        ->assertDontSee(__('Shortcuts'))
        ->assertDontSee('Asamblea de Delegados')
        ->assertDontSee(__('Teachers on the roll'));
});
