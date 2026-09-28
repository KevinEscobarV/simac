<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceMovement;
use App\Events\AttendanceChanged;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CheckIn
{
    /**
     * Register a teacher's arrival. If they had left, their attendance is
     * reopened keeping the original check-in time.
     *
     * @throws ValidationException when the assembly is closed or the teacher retired
     */
    public function handle(Assembly $assembly, Teacher $teacher, ?User $registeredBy = null): AttendanceMovement
    {
        $assembly->ensureOpen();

        if ($teacher->trashed()) {
            throw ValidationException::withMessages([
                'teacher' => __(':name was retired from the roll.', ['name' => $teacher->name]),
            ]);
        }

        // Two desks may register the same person at once: the unique index
        // settles it and the second one just finds the first check-in.
        $attendance = Attendance::createOrFirst(
            ['assembly_id' => $assembly->id, 'teacher_id' => $teacher->id],
            ['checked_in_at' => now(), 'registered_by' => $registeredBy?->id],
        );

        if (! $attendance->wasRecentlyCreated && $attendance->isPresent()) {
            return AttendanceMovement::AlreadyPresent;
        }

        if (! $attendance->wasRecentlyCreated) {
            $attendance->update(['checked_out_at' => null]);
        }

        broadcast(new AttendanceChanged($assembly->id))->toOthers();

        return $attendance->wasRecentlyCreated ? AttendanceMovement::CheckIn : AttendanceMovement::Reentry;
    }
}
