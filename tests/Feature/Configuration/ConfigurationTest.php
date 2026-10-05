<?php

use App\Enums\Role;
use App\Events\ProjectionUpdated;
use App\Livewire\Configuration\Index;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('configuration'))->assertRedirect(route('login'));
});

test('only administrators open the configuration', function (Role $role, bool $allowed) {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('configuration'))->assertStatus($allowed ? 200 : 403);
})->with([
    'administrator' => [Role::Admin, true],
    'registrar' => [Role::Registrar, false],
    'projector' => [Role::Projector, false],
]);

test('the event title and subtitle are saved, and blank ones are saved as none', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->set('form.title', '  Juegos   Deportivos del Magisterio 2026 ')
        ->set('form.subtitle', 'Yopal, 10 al 12 de octubre')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::current())
        ->event_title->toBe('Juegos Deportivos del Magisterio 2026')
        ->event_subtitle->toBe('Yopal, 10 al 12 de octubre');

    Livewire::test(Index::class)
        ->assertSet('form.title', 'Juegos Deportivos del Magisterio 2026')
        ->set('form.subtitle', '   ')
        ->call('save');

    expect(Setting::current()->event_subtitle)->toBeNull();
});

test('a new image is stored on the public disk and replaces the previous one', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->set('form.image', UploadedFile::fake()->image('mascota.png', 1067, 1600))
        ->call('save')
        ->assertHasNoErrors();

    $first = Setting::current()->event_image_path;
    Storage::disk('public')->assertExists($first);

    Livewire::test(Index::class)
        ->set('form.image', UploadedFile::fake()->image('mascota-nueva.webp', 800, 1200))
        ->call('save');

    $second = Setting::current()->event_image_path;

    expect($second)->not->toBe($first)->toStartWith(Setting::EVENT_FOLDER.'/');
    Storage::disk('public')->assertExists($second);
    Storage::disk('public')->assertMissing($first);
});

test('removing the image deletes its file and leaves the texts', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $path = UploadedFile::fake()->image('mascota.png')->store(Setting::EVENT_FOLDER, 'public');
    Setting::factory()->withEvent()->create(['event_image_path' => $path]);

    Livewire::test(Index::class)->call('removeImage');

    expect(Setting::current())
        ->event_image_path->toBeNull()
        ->event_title->not->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('only pictures up to 5 MB are taken, and never SVG', function (UploadedFile $file) {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->set('form.image', $file)
        ->assertHasErrors('form.image')
        ->call('save')
        ->assertHasErrors('form.image');

    expect(Setting::current()->event_image_path)->toBeNull();
})->with([
    'a PDF' => fn () => UploadedFile::fake()->create('acta.pdf', 120, 'application/pdf'),
    'an SVG' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'a picture over 5 MB' => fn () => UploadedFile::fake()->image('mascota.png')->size(5 * 1024 + 1),
]);

test('a switch for the projection screen is saved on flipping it, and the screens on hear it at once', function () {
    Event::fake([ProjectionUpdated::class]);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->assertSet('screen_shows_membership', false)
        ->set('screen_shows_membership', true)
        ->set('screen_shows_participants', false);

    expect(Setting::current())
        ->screen_shows_membership->toBeTrue()
        ->screen_shows_participants->toBeFalse()
        ->screen_shows_filters->toBeTrue();
    Event::assertDispatched(ProjectionUpdated::class);
});
