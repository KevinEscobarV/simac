<?php

use App\Livewire\Teachers\Cards;
use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;

function cardsCount(int $count): string
{
    return trans_choice('{0} No cards|{1} :count card|[2,*] :count cards', $count);
}

test('guests are redirected to the login page', function () {
    $this->get(route('teachers.cards'))->assertRedirect(route('login'));
});

test('only administrators print cards', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get(route('teachers.cards'))->assertForbidden();
    $this->get(route('teachers.cards.print'))->assertForbidden();
})->with(['registrar', 'projector']);

test('the cards page counts the active teachers the filters select', function () {
    $this->actingAs(User::factory()->admin()->create());
    $yopal = City::factory()->create();
    $braulio = School::factory()->for($yopal)->create();
    $manuela = School::factory()->for($yopal)->create();
    Teacher::factory()->for($braulio)->create();
    Teacher::factory()->for($braulio)->create()->delete();
    Teacher::factory()->for($manuela)->nonMember()->create();
    Teacher::factory()->create();

    Livewire::test(Cards::class)
        ->assertSee(cardsCount(3))
        ->set('city', (string) $yopal->id)
        ->assertSee(cardsCount(2))
        ->set('school', (string) $braulio->id)
        ->assertSee(cardsCount(1))
        ->set('school', '')
        ->set('membership', 'no')
        ->assertSee(cardsCount(1))
        ->assertSee(e(route('teachers.cards.print', ['municipio' => $yopal->id, 'afiliacion' => 'no'])), false);
});

test('the page previews the first sheet only', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();
    $teachers = collect(range(1, 9))->map(fn (int $number) => Teacher::factory()->for($school)->create(['name' => sprintf('Docente %02d', $number)]));

    Livewire::test(Cards::class)
        ->assertSee(trans_choice('{1} :count letter sheet, 8 cards per sheet|[2,*] :count letter sheets, 8 cards per sheet', 2))
        ->assertSee($teachers->take(8)->pluck('name')->all())
        ->assertDontSee('Docente 09');
});

test('the sheet lays out the cards eight per page, sorted to hand them out', function () {
    $this->actingAs(User::factory()->admin()->create());
    $aguazul = School::factory()->for(City::factory()->create(['name' => 'Aguazul']))->create();
    $yopal = School::factory()->for(City::factory()->create(['name' => 'Yopal']))->create();
    $last = Teacher::factory()->for($yopal)->create(['name' => 'Ana Rojas']);
    $first = Teacher::factory()->for($aguazul)->create(['name' => 'Zoila Pérez']);
    Teacher::factory()->count(7)->for($aguazul)->create();

    $response = $this->get(route('teachers.cards.print'))
        ->assertOk()
        ->assertSeeInOrder([$first->name, $last->name])
        ->assertSee([$first->code, __('Barcode :value', ['value' => $first->code])]);

    expect(substr_count($response->getContent(), 'data-sheet'))->toBe(2);
});

test('the sheet follows the filters of the link', function () {
    $this->actingAs(User::factory()->admin()->create());
    $school = School::factory()->create();
    $member = Teacher::factory()->for($school)->create();
    $nonMember = Teacher::factory()->for($school)->nonMember()->create();
    $elsewhere = Teacher::factory()->nonMember()->create();

    $this->get(route('teachers.cards.print', ['municipio' => $school->city_id, 'afiliacion' => 'no']))
        ->assertOk()
        ->assertSee($nonMember->name)
        ->assertDontSee($member->name)
        ->assertDontSee($elsewhere->name);
});

test('a single card is printed from the teacher, never for someone retired', function () {
    $this->actingAs(User::factory()->admin()->create());
    $teacher = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $retired = Teacher::factory()->create();
    $retired->delete();

    $this->get(route('teachers.cards.print', ['docente' => $teacher->id]))
        ->assertOk()
        ->assertSee($teacher->name)
        ->assertDontSee($other->name);

    $this->get(route('teachers.cards.print', ['docente' => $retired->id]))->assertNotFound();
});
