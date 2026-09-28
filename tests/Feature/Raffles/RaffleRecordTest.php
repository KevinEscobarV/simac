<?php

use App\Enums\QuorumType;
use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\View;

/**
 * The HTML the PDF was drawn from: the view the download rendered, rendered
 * again with the same data. The PDF itself stores its text as glyphs.
 */
function recordHtml(Raffle $raffle): string
{
    $record = null;

    View::composer('pdf.raffle', function (Illuminate\View\View $view) use (&$record): void {
        $record ??= $view;
    });

    test()->get(route('raffles.pdf', $raffle))->assertOk();

    return $record->render();
}

test('guests are redirected to the login page', function () {
    $this->get(route('raffles.pdf', Raffle::factory()->create()))->assertRedirect(route('login'));
});

test('only administrators download records', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get(route('raffles.pdf', Raffle::factory()->create()))->assertForbidden();
})->with(['registrar', 'projector']);

test('administrators download the record as a PDF', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()->drawnAmong(Teacher::factory()->count(3)->create())->create();

    $response = $this->get(route('raffles.pdf', $raffle))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload("acta-de-sorteo-{$raffle->id}.pdf");

    expect($response->getContent())->toStartWith('%PDF-');
});

test('the record names the winners in order, every participant and leaves room to sign', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()
        ->for(Assembly::factory()->closed()->withQuorum(QuorumType::Count, 3)->create(['name' => 'Asamblea de Delegados']))
        ->drawnAmong(Teacher::factory()->count(6)->create())
        ->create([
            'prize' => 'Bicicleta todoterreno',
            'winners_count' => 2,
            'filter_description' => 'Solo afiliados',
            'quorum_met' => false,
            'drawn_by' => User::factory()->admin()->create(['name' => 'Rocío Galindo'])->id,
        ]);

    $html = recordHtml($raffle);

    expect($html)->toContain('Bicicleta todoterreno', 'Asamblea de Delegados', 'Solo afiliados', 'Rocío Galindo')
        ->toContain(__('Not met when drawing'))
        ->toContain(__('Signature'), __('Office held'))
        ->toContain(...$raffle->participants->pluck('code')->all());

    [$first, $second] = $raffle->winners->all();
    expect(strpos($html, e($first->name)))->toBeLessThan(strpos($html, e($second->name)));
});

test('the record waits until the screen has shown every winner', function () {
    $this->actingAs(User::factory()->admin()->create());
    $raffle = Raffle::factory()->drawnAmong(Teacher::factory()->count(3)->create())->create();
    $projection = Projection::current();
    $projection->prepare($raffle);
    $projection->launch();

    $this->get(route('raffles.pdf', $raffle))
        ->assertForbidden()
        ->assertSee(__('The record can be downloaded once the screen has shown every winner.'));

    $projection->finish(1);

    $this->get(route('raffles.pdf', $raffle))->assertOk();
});
