<?php

use App\Enums\QuorumType;
use App\Livewire\Assemblies\Index;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('assemblies.index'))->assertRedirect(route('login'));
});

test('users without permission to manage assemblies are forbidden', function () {
    $this->actingAs(User::factory()->registrar()->create());

    $this->get(route('assemblies.index'))->assertForbidden();
});

test('administrators can open an assembly with its quorum', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('startOpening')
        ->assertSet('form.date', today()->toDateString())
        ->set('form.name', ' Asamblea  General Ordinaria ')
        ->set('form.location', 'Sede sindical · Yopal')
        ->set('form.quorum_type', 'count')
        ->set('form.quorum_value', '7')
        ->call('open')
        ->assertHasNoErrors();

    $assembly = Assembly::sole();

    expect($assembly->name)->toBe('Asamblea General Ordinaria')
        ->and($assembly->isOpen())->toBeTrue()
        ->and($assembly->quorum_type)->toBe(QuorumType::Count)
        ->and($assembly->quorum_value)->toBe(7.0)
        ->and($assembly->opener->is($admin))->toBeTrue();
});

test('an assembly can be opened without a quorum', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('startOpening')
        ->set('form.name', 'Reunión de junta')
        ->set('form.quorum_type', 'none')
        ->set('form.quorum_value', '')
        ->call('open')
        ->assertHasNoErrors();

    expect(Assembly::sole()->quorum_type)->toBeNull();
});

test('the quorum must be a whole number of members or a percentage up to 100', function (string $type, string $value) {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('startOpening')
        ->set('form.name', 'Asamblea General Ordinaria')
        ->set('form.quorum_type', $type)
        ->set('form.quorum_value', $value)
        ->call('open')
        ->assertHasErrors('form.quorum_value');

    expect(Assembly::count())->toBe(0);
})->with([
    'percentage above 100' => ['percentage', '101'],
    'zero percent' => ['percentage', '0'],
    'fraction of a member' => ['count', '6.5'],
    'missing value' => ['count', ''],
]);

test('the panel shows who is present and how far the quorum is', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->withQuorum(QuorumType::Count, 3)->create(['name' => 'Asamblea General Ordinaria']);
    Attendance::factory()->for($assembly)->count(2)->create();
    Attendance::factory()->for($assembly)->checkedOut()->create();

    Livewire::test(Index::class)
        ->assertSee('Asamblea General Ordinaria')
        ->assertSee(trans_choice('{1} :count member missing|[2,*] :count members missing', 1));
});

test('administrators can check teachers in and out from the roll', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->create();
    $teacher = Teacher::factory()->create();

    $component = Livewire::test(Index::class)->call('checkIn', $teacher->id);

    expect($assembly->attendances()->sole()->isPresent())->toBeTrue();

    $component->call('checkOut', $teacher->id);

    expect($assembly->attendances()->sole()->isPresent())->toBeFalse();
});

test('pressing Enter in the search checks in the teacher it points to', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->create();
    $teacher = Teacher::factory()->create(['document_number' => '1118541203']);

    Livewire::test(Index::class)
        ->set('search', '1.118.541.203')
        ->call('checkInFromSearch')
        ->assertHasNoErrors()
        ->assertSet('search', '');

    expect($assembly->attendances()->sole()->teacher->is($teacher))->toBeTrue();
});

test('pressing Enter with an ambiguous search checks nobody in', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->create();
    Teacher::factory()->create(['name' => 'Luz Dary Camargo']);
    Teacher::factory()->create(['name' => 'Luz Marina Rojas']);

    Livewire::test(Index::class)
        ->set('search', 'luz')
        ->call('checkInFromSearch')
        ->assertHasErrors('key')
        ->assertSet('search', 'luz');

    expect($assembly->attendances()->count())->toBe(0);
});

test('a record made by mistake can be voided', function () {
    $this->actingAs(User::factory()->admin()->create());
    $attendance = Attendance::factory()->create();

    Livewire::test(Index::class)
        ->call('confirmVoiding', $attendance->teacher_id)
        ->call('voidAttendance');

    $this->assertModelMissing($attendance);
});

test('the roll can be filtered by attendance', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->create();
    Attendance::factory()->for($assembly)->for(Teacher::factory()->create(['name' => 'Presente Uno']))->create();
    Attendance::factory()->for($assembly)->for(Teacher::factory()->create(['name' => 'Salió Dos']))->checkedOut()->create();
    Teacher::factory()->create(['name' => 'Ausente Tres']);

    Livewire::test(Index::class)
        ->set('filter', 'presentes')
        ->assertSee('Presente Uno')
        ->assertDontSee('Salió Dos')
        ->assertDontSee('Ausente Tres')
        ->set('filter', 'salieron')
        ->assertSee('Salió Dos')
        ->assertDontSee('Presente Uno')
        ->set('filter', 'sin-registrar')
        ->assertSee('Ausente Tres')
        ->assertDontSee('Presente Uno');
});

test('the quorum can be adjusted while the assembly is open', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->withQuorum(QuorumType::Percentage, 50)->create();

    Livewire::test(Index::class)
        ->call('editQuorum')
        ->assertSet('quorumForm.quorum_type', 'percentage')
        ->assertSet('quorumForm.quorum_value', '50')
        ->set('quorumForm.quorum_value', '66.5')
        ->call('saveQuorum')
        ->assertHasNoErrors();

    expect($assembly->refresh()->quorum_value)->toBe(66.5);
});

test('administrators can close an assembly and reopen it', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Assembly::factory()->create();

    $component = Livewire::test(Index::class)
        ->call('confirmClosing')
        ->call('close');

    expect($assembly->refresh()->isOpen())->toBeFalse();

    $component->call('reopen', $assembly->id);

    expect($assembly->refresh()->isOpen())->toBeTrue();
});

test('an assembly with attendance cannot be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());
    $assembly = Attendance::factory()->create()->assembly;
    $assembly->update(['closed_at' => now()]);

    Livewire::test(Index::class)
        ->call('confirmDeletion', $assembly->id)
        ->assertNotSet('deletionBlocker', null)
        ->call('delete')
        ->assertHasErrors('assembly');

    $this->assertModelExists($assembly);
});
