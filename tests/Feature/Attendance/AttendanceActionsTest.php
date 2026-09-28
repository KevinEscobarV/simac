<?php

use App\Actions\Attendance\CheckIn;
use App\Actions\Attendance\CheckOut;
use App\Actions\Attendance\UndoMovement;
use App\Actions\Attendance\VoidAttendance;
use App\Enums\AttendanceMovement;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('checking in registers the arrival and who registered it', function () {
    $assembly = Assembly::factory()->create();
    $teacher = Teacher::factory()->create();
    $registrar = User::factory()->registrar()->create();

    $movement = app(CheckIn::class)->handle($assembly, $teacher, $registrar);

    $attendance = $assembly->attendances()->sole();

    expect($movement)->toBe(AttendanceMovement::CheckIn)
        ->and($attendance->teacher->is($teacher))->toBeTrue()
        ->and($attendance->isPresent())->toBeTrue()
        ->and($attendance->registered_by)->toBe($registrar->id);
});

test('checking in someone already present changes nothing', function () {
    $this->freezeSecond();
    $attendance = Attendance::factory()->create(['checked_in_at' => now()->subHour()]);

    $movement = app(CheckIn::class)->handle($attendance->assembly, $attendance->teacher);

    expect($movement)->toBe(AttendanceMovement::AlreadyPresent)
        ->and($attendance->refresh()->checked_in_at->equalTo(now()->subHour()))->toBeTrue()
        ->and(Attendance::count())->toBe(1);
});

test('checking in someone who left lets them back in, keeping the first check-in time', function () {
    $this->freezeSecond();
    $attendance = Attendance::factory()->checkedOut()->create(['checked_in_at' => now()->subHour()]);

    $movement = app(CheckIn::class)->handle($attendance->assembly, $attendance->teacher);

    $attendance->refresh();

    expect($movement)->toBe(AttendanceMovement::Reentry)
        ->and($attendance->isPresent())->toBeTrue()
        ->and($attendance->checked_in_at->equalTo(now()->subHour()))->toBeTrue();
});

test('a closed assembly takes no more attendance', function () {
    $assembly = Assembly::factory()->closed()->create();

    expect(fn () => app(CheckIn::class)->handle($assembly, Teacher::factory()->create()))
        ->toThrow(ValidationException::class);

    expect(Attendance::count())->toBe(0);
});

test('a retired teacher cannot be checked in', function () {
    $teacher = Teacher::factory()->trashed()->create();

    expect(fn () => app(CheckIn::class)->handle(Assembly::factory()->create(), $teacher))
        ->toThrow(ValidationException::class);
});

test('a teacher can only check out while present', function () {
    $attendance = Attendance::factory()->create();

    app(CheckOut::class)->handle($attendance->assembly, $attendance->teacher);

    expect($attendance->refresh()->isPresent())->toBeFalse()
        ->and(fn () => app(CheckOut::class)->handle($attendance->assembly, $attendance->teacher))
        ->toThrow(ValidationException::class);
});

test('voiding deletes the record altogether', function () {
    $attendance = Attendance::factory()->checkedOut()->create();

    app(VoidAttendance::class)->handle($attendance->assembly, $attendance->teacher);

    $this->assertModelMissing($attendance);
});

test('undoing a check-in voids it', function () {
    $attendance = Attendance::factory()->create();

    app(UndoMovement::class)->handle($attendance->assembly, $attendance->teacher, AttendanceMovement::CheckIn);

    $this->assertModelMissing($attendance);
});

test('undoing a re-entry checks them out again', function () {
    $attendance = Attendance::factory()->create();

    app(UndoMovement::class)->handle($attendance->assembly, $attendance->teacher, AttendanceMovement::Reentry);

    expect($attendance->refresh()->isPresent())->toBeFalse();
});

test('undoing a check-out makes them present again, keeping the check-in time', function () {
    $this->freezeSecond();
    $attendance = Attendance::factory()->checkedOut()->create(['checked_in_at' => now()->subHour()]);

    app(UndoMovement::class)->handle($attendance->assembly, $attendance->teacher, AttendanceMovement::CheckOut);

    $attendance->refresh();

    expect($attendance->isPresent())->toBeTrue()
        ->and($attendance->checked_in_at->equalTo(now()->subHour()))->toBeTrue();
});

test('an undo is refused when the record changed in the meantime', function () {
    $attendance = Attendance::factory()->checkedOut()->create();

    expect(fn () => app(UndoMovement::class)->handle($attendance->assembly, $attendance->teacher, AttendanceMovement::CheckIn))
        ->toThrow(ValidationException::class);

    $this->assertModelExists($attendance);
});
