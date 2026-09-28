<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceMovement;
use App\Events\AttendanceChanged;
use App\Models\Assembly;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

class CheckOut
{
    /**
     * Register that a teacher left. Only possible while they are present.
     *
     * @throws ValidationException
     */
    public function handle(Assembly $assembly, Teacher $teacher): AttendanceMovement
    {
        $assembly->ensureOpen();

        $updated = $assembly->attendances()
            ->whereBelongsTo($teacher)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        if ($updated === 0) {
            throw ValidationException::withMessages([
                'teacher' => __(':name is not checked in at this assembly.', ['name' => $teacher->name]),
            ]);
        }

        broadcast(new AttendanceChanged($assembly->id))->toOthers();

        return AttendanceMovement::CheckOut;
    }
}
