<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceMovement;
use App\Events\AttendanceChanged;
use App\Models\Assembly;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

class UndoMovement
{
    /**
     * Revert the last movement, so a slip at the desk does not need the
     * administrator:
     *  - a check-in is voided;
     *  - a re-entry checks them out again, at the current time (the original
     *    check-out time was cleared when they came back);
     *  - a check-out reopens their attendance, keeping the check-in time.
     *
     * @throws ValidationException when the record changed in the meantime
     */
    public function handle(Assembly $assembly, Teacher $teacher, AttendanceMovement $movement): void
    {
        $assembly->ensureOpen();

        $attendance = $assembly->attendances()->whereBelongsTo($teacher)->first();

        $undone = match ($movement) {
            AttendanceMovement::CheckIn => $attendance?->isPresent() && $attendance->delete(),
            AttendanceMovement::Reentry => $attendance?->isPresent() && $attendance->update(['checked_out_at' => now()]),
            AttendanceMovement::CheckOut => $attendance !== null && ! $attendance->isPresent() && $attendance->update(['checked_out_at' => null]),
            AttendanceMovement::AlreadyPresent => false,
        };

        if (! $undone) {
            throw ValidationException::withMessages([
                'teacher' => __('The record of :name changed in the meantime: check their status.', ['name' => $teacher->name]),
            ]);
        }

        broadcast(new AttendanceChanged($assembly->id))->toOthers();
    }
}
