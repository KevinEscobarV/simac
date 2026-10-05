<?php

use App\Actions\Raffles\DrawRaffle;
use App\Enums\RaffleAnimation;
use App\Livewire\Screen\Index as Screen;
use App\Models\Projection;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use App\Support\RaffleFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function eventWithImage(): Setting
{
    Storage::fake('public');

    return Setting::factory()->withEvent()->create([
        'event_image_path' => UploadedFile::fake()->image('mascota.png')->store(Setting::EVENT_FOLDER, 'public'),
    ]);
}

test('the home page presents the event', function () {
    $settings = eventWithImage();
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))
        ->assertSee($settings->event_title)
        ->assertSee($settings->event_subtitle)
        ->assertSee($settings->eventImageUrl());
});

test('the sign-in page presents the event instead of its usual text', function () {
    $settings = eventWithImage();

    $this->get(route('login'))
        ->assertSee($settings->event_title)
        ->assertSee($settings->eventImageUrl())
        ->assertDontSee(__('Roll, attendance and raffles'));
});

test('with no event, the sign-in page and the home page show only SIMAC', function () {
    $this->get(route('login'))->assertSee(__('Roll, attendance and raffles'));

    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))->assertDontSeeText(__('Event'));
});

test('the screen presents the event at rest and keeps its name in view during a raffle', function () {
    $settings = eventWithImage();
    $this->actingAs(User::factory()->projector()->create());

    Livewire::test(Screen::class)
        ->assertSee($settings->event_title)
        ->assertSee($settings->eventImageUrl());

    Teacher::factory()->count(4)->create();
    Projection::recordScreen('hall');
    app(DrawRaffle::class)->handle(User::factory()->admin()->create(), new RaffleFilters, 'Bicicleta todoterreno', 1, RaffleAnimation::Wheel);
    Projection::current()->launch();

    Livewire::test(Screen::class)
        ->assertSee('Bicicleta todoterreno')
        ->assertSee($settings->event_title)
        ->assertSee($settings->eventImageUrl());
});
