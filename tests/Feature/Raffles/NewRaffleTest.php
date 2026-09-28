<?php

use App\Enums\ProjectionPhase;
use App\Enums\RaffleAnimation;
use App\Livewire\Raffles\Create;
use App\Livewire\Raffles\LiveBadge;
use App\Models\City;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Validation\Rules\Enum;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('raffles.create'))->assertRedirect(route('login'));
});

test('only administrators can draw raffles', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get(route('raffles.create'))->assertForbidden();
})->with(['registrar', 'projector']);

test('administrators draw a raffle and the page turns into the console', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    Teacher::factory()->count(3)->create();

    Livewire::test(Create::class)
        ->set('form.prize', '  Bicicleta   todoterreno ')
        ->set('form.winners_count', '2')
        ->set('form.animation', 'drum')
        ->call('draw')
        ->assertHasNoErrors()
        ->assertSet('form.prize', '')
        ->assertDispatched('projection-changed')
        ->assertSee(__('Ready to project'));

    $raffle = Raffle::sole();

    expect($raffle->prize)->toBe('Bicicleta todoterreno')
        ->and($raffle->winners_count)->toBe(2)
        ->and($raffle->animation)->toBe(RaffleAnimation::Drum)
        ->and($raffle->drawer->is($admin))->toBeTrue()
        ->and(Projection::current()->phase)->toBe(ProjectionPhase::Ready);
});

test('the prize, the number of winners and the animation are required and checked', function (string $field, string $value, string $rule) {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(3)->create();

    Livewire::test(Create::class)
        ->set('form.prize', 'Bicicleta')
        ->set("form.{$field}", $value)
        ->call('draw')
        ->assertHasErrors(["form.{$field}" => $rule]);

    expect(Raffle::count())->toBe(0);
})->with([
    'no prize' => ['prize', '   ', 'required'],
    'no winners' => ['winners_count', '0', 'min'],
    'too many winners' => ['winners_count', '21', 'max'],
    'an unknown animation' => ['animation', 'fireworks', Enum::class],
]);

test('the summary counts the participants as the filters change', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(2)->create();
    Teacher::factory()->nonMember()->create();

    $raffle = Livewire::test(Create::class);
    expect($raffle->instance()->participantsCount)->toBe(3);

    $raffle->set('form.union_members_only', true);
    expect($raffle->instance()->participantsCount)->toBe(2);
});

test('choosing another municipality clears the school', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();

    Livewire::test(Create::class)
        ->set('form.city_id', (string) $school->city_id)
        ->set('form.school_id', (string) $school->id)
        ->set('form.city_id', (string) City::factory()->create()->id)
        ->assertSet('form.school_id', '');
});

test('why a raffle could not be drawn stays on screen', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(3)->create();

    Livewire::test(Create::class)
        ->set('form.prize', 'Bicicleta')
        ->set('form.present_only', true)
        ->call('draw')
        ->assertHasErrors('form.draw')
        ->call('$refresh')
        ->assertHasErrors('form.draw');

    expect(Raffle::count())->toBe(0);
});

test('the console keeps the winner hidden until the screen reveals it, unless asked to see it before', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(3)->create();

    $console = Livewire::test(Create::class)
        ->set('form.prize', 'Bicicleta')
        ->call('draw');

    $winner = Raffle::sole()->winners->sole();

    $console->assertDontSee($winner->name)
        ->call('peek')
        ->assertSee($winner->name)
        ->assertSee(__('Only you can see it: the screen has not shown it yet.'));
});

test('the console launches each winner, follows the screen and releases it', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(4)->create();
    Projection::recordScreen('hall');

    $console = Livewire::test(Create::class)
        ->set('form.prize', 'Bicicleta')
        ->set('form.winners_count', '2')
        ->call('draw')
        ->call('launch');

    [$first, $second] = Raffle::sole()->winners->all();
    expect(Projection::current()->phase)->toBe(ProjectionPhase::Animating);

    Projection::current()->finish(1);
    $console->call('refreshLive')
        ->assertSee($first->name)
        ->assertSee(__('Next winner (:position of :total)', ['position' => 2, 'total' => 2]))
        ->call('launch');

    Projection::current()->finish(2);
    $console->call('refreshLive')
        ->assertSee($second->name)
        ->assertSee(__('Finish and release'))
        ->call('release')
        ->assertSee(__('New raffle'));

    expect(Projection::current()->phase)->toBe(ProjectionPhase::Idle);
});

test('a console order that no longer applies is only a notice', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(3)->create();
    Projection::recordScreen('hall');

    Livewire::test(Create::class)
        ->set('form.prize', 'Bicicleta')
        ->call('draw')
        ->call('launch')
        ->call('launch')
        ->assertHasNoErrors();

    expect(Projection::current()->attempt)->toBe(1);
});

test('the console does not launch while no screen is on', function () {
    $this->actingAs(User::factory()->admin()->create());
    Teacher::factory()->count(3)->create();

    $console = Livewire::test(Create::class)
        ->set('form.prize', 'Bicicleta')
        ->call('draw')
        ->assertSee(__('(or reload it) to launch.'))
        ->call('launch');

    expect(Projection::current()->phase)->toBe(ProjectionPhase::Ready);

    Projection::recordScreen('hall');
    $console->call('refreshLive')->assertDontSee(__('(or reload it) to launch.'));
});

test('the navigation marks a raffle on screen as live', function () {
    $this->actingAs(User::factory()->admin()->create());

    $badge = Livewire::test(LiveBadge::class)->assertDontSee(__('LIVE'));

    Projection::current()->prepare(Raffle::factory()->create());

    $badge->call('refreshLive')->assertSee(__('LIVE'));
});
