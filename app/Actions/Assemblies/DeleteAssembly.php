<?php

namespace App\Actions\Assemblies;

use App\Models\Assembly;
use Illuminate\Validation\ValidationException;

class DeleteAssembly
{
    /**
     * Why the assembly cannot be deleted, or null when nobody was registered
     * at it (it was opened by mistake). One with attendance is history.
     */
    public function blocker(Assembly $assembly): ?string
    {
        $attendances = $assembly->attendances()->count();

        if ($attendances === 0) {
            return null;
        }

        return trans_choice('It has :count check-in on record, so it is kept as history.|It has :count check-ins on record, so it is kept as history.', $attendances);
    }

    /**
     * @throws ValidationException
     */
    public function handle(Assembly $assembly): void
    {
        $blocker = $this->blocker($assembly);

        if ($blocker !== null) {
            throw ValidationException::withMessages(['assembly' => $blocker]);
        }

        $assembly->delete();
    }
}
