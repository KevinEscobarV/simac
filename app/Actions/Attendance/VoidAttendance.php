<?php

namespace App\Actions\Attendance;

use App\Models\Assembly;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

class VoidAttendance
{
    /**
     * Remove a teacher's record from the assembly altogether, to fix a
     * check-in made by mistake.
     *
     * @throws ValidationException
     */
    public function handle(Assembly $assembly, Teacher $teacher): void
    {
        $assembly->ensureOpen();

        $deleted = $assembly->attendances()->whereBelongsTo($teacher)->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'teacher' => __(':name has no record at this assembly.', ['name' => $teacher->name]),
            ]);
        }
    }
}
