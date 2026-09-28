<?php

use App\Actions\Assemblies\CloseAssembly;
use App\Actions\Assemblies\DeleteAssembly;
use App\Actions\Assemblies\OpenAssembly;
use App\Actions\Assemblies\ReopenAssembly;
use App\Enums\QuorumType;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Raffle;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * @return array{name: string, date: string, location: string|null, quorum_type: QuorumType|null, quorum_value: float|null}
 */
function assemblyAttributes(): array
{
    return [
        'name' => 'Asamblea General Ordinaria',
        'date' => '2026-10-02',
        'location' => 'Sede sindical · Yopal',
        'quorum_type' => QuorumType::Percentage,
        'quorum_value' => 50.0,
    ];
}

test('opening an assembly records who opened it', function () {
    $admin = User::factory()->admin()->create();

    $assembly = app(OpenAssembly::class)->handle($admin, assemblyAttributes());

    expect($assembly->isOpen())->toBeTrue()
        ->and($assembly->opener->is($admin))->toBeTrue()
        ->and($assembly->quorum_type)->toBe(QuorumType::Percentage)
        ->and($assembly->date->toDateString())->toBe('2026-10-02');
});

test('only one assembly can be open at a time', function () {
    Assembly::factory()->create();

    expect(fn () => app(OpenAssembly::class)->handle(User::factory()->admin()->create(), assemblyAttributes()))
        ->toThrow(ValidationException::class);

    expect(Assembly::count())->toBe(1);
});

test('closing an assembly keeps who was still present', function () {
    $attendance = Attendance::factory()->create();

    app(CloseAssembly::class)->handle($attendance->assembly);

    expect($attendance->assembly->refresh()->isOpen())->toBeFalse()
        ->and($attendance->refresh()->isPresent())->toBeTrue();
});

test('a closed assembly can be reopened only when no other one is open', function () {
    $closed = Assembly::factory()->closed()->create();
    $open = Assembly::factory()->create();

    expect(fn () => app(ReopenAssembly::class)->handle($closed))->toThrow(ValidationException::class)
        ->and($closed->refresh()->isOpen())->toBeFalse();

    app(CloseAssembly::class)->handle($open);
    app(ReopenAssembly::class)->handle($closed);

    expect($closed->refresh()->isOpen())->toBeTrue();
});

test('an assembly can be deleted only if nobody was registered at it', function () {
    $empty = Assembly::factory()->closed()->create();
    $withAttendance = Attendance::factory()->create()->assembly;

    app(DeleteAssembly::class)->handle($empty);

    expect(fn () => app(DeleteAssembly::class)->handle($withAttendance))->toThrow(ValidationException::class);

    $this->assertModelMissing($empty);
    $this->assertModelExists($withAttendance);
});

test('an assembly with raffles is kept as history too', function () {
    $assembly = Raffle::factory()->for(Assembly::factory()->closed())->create()->assembly;

    expect(app(DeleteAssembly::class)->blocker($assembly))->not->toBeNull()
        ->and(fn () => app(DeleteAssembly::class)->handle($assembly))->toThrow(ValidationException::class);

    $this->assertModelExists($assembly);
});
