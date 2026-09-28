<?php

use App\Actions\Assemblies\AdjustQuorum;
use App\Actions\Assemblies\CloseAssembly;
use App\Actions\Assemblies\DeleteAssembly;
use App\Actions\Assemblies\OpenAssembly;
use App\Actions\Assemblies\ReopenAssembly;
use App\Actions\Attendance\CheckIn;
use App\Actions\Attendance\CheckOut;
use App\Actions\Attendance\UndoMovement;
use App\Actions\Attendance\VoidAttendance;
use App\Enums\AttendanceMovement;
use App\Enums\QuorumType;
use App\Events\AssemblyChanged;
use App\Events\AttendanceChanged;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Validation\ValidationException;

test('every attendance change is announced to the panel and the desks', function () {
    Event::fake([AttendanceChanged::class]);
    $assembly = Assembly::factory()->create();
    $teacher = Teacher::factory()->create();
    $mistake = Attendance::factory()->for($assembly)->create();

    app(CheckIn::class)->handle($assembly, $teacher);
    app(CheckOut::class)->handle($assembly, $teacher);
    app(CheckIn::class)->handle($assembly, $teacher);
    app(UndoMovement::class)->handle($assembly, $teacher, AttendanceMovement::Reentry);
    app(VoidAttendance::class)->handle($assembly, $mistake->teacher);

    Event::assertDispatchedTimes(AttendanceChanged::class, 5);
    Event::assertDispatched(AttendanceChanged::class, fn (AttendanceChanged $event) => $event->assemblyId === $assembly->id);
});

test('a movement that changes nothing announces nothing', function () {
    Event::fake([AttendanceChanged::class]);
    $attendance = Attendance::factory()->create();

    app(CheckIn::class)->handle($attendance->assembly, $attendance->teacher);

    expect(fn () => app(CheckOut::class)->handle($attendance->assembly, Teacher::factory()->create()))
        ->toThrow(ValidationException::class);

    Event::assertNotDispatched(AttendanceChanged::class);
});

test('opening, adjusting, closing, reopening and deleting an assembly are announced', function () {
    Event::fake([AssemblyChanged::class]);

    $assembly = app(OpenAssembly::class)->handle(User::factory()->admin()->create(), [
        'name' => 'Asamblea General Ordinaria',
        'date' => '2026-10-02',
        'location' => null,
        'quorum_type' => null,
        'quorum_value' => null,
    ]);
    app(AdjustQuorum::class)->handle($assembly, ['quorum_type' => QuorumType::Count, 'quorum_value' => 10.0]);
    app(CloseAssembly::class)->handle($assembly);
    app(ReopenAssembly::class)->handle($assembly);
    app(CloseAssembly::class)->handle($assembly);
    app(DeleteAssembly::class)->handle($assembly);

    Event::assertDispatchedTimes(AssemblyChanged::class, 6);
    Event::assertDispatched(AssemblyChanged::class, fn (AssemblyChanged $event) => $event->assemblyId === $assembly->id);
});

test('a check-in is kept when the live updates are down', function () {
    Exceptions::fake();
    Broadcast::extend('down', fn () => new class extends Broadcaster
    {
        public function auth($request) {}

        public function validAuthenticationResponse($request, $result) {}

        public function broadcast(array $channels, $event, array $payload = []): void
        {
            throw new BroadcastException('Reverb is not running.');
        }
    });
    config(['broadcasting.connections.down' => ['driver' => 'down'], 'broadcasting.default' => 'down']);
    $assembly = Assembly::factory()->create();

    $movement = app(CheckIn::class)->handle($assembly, Teacher::factory()->create());

    expect($movement)->toBe(AttendanceMovement::CheckIn)
        ->and($assembly->attendances()->count())->toBe(1);

    Exceptions::assertReported(BroadcastException::class);
});
