<?php

namespace App\Actions\Assemblies;

use App\Events\AssemblyChanged;
use App\Models\Assembly;
use Illuminate\Validation\ValidationException;

class DeleteAssembly
{
    /**
     * Why the assembly cannot be deleted, or null when nothing was recorded
     * at it (it was opened by mistake). One with attendance or raffles is
     * history.
     */
    public function blocker(Assembly $assembly): ?string
    {
        $attendances = $assembly->attendances()->count();

        if ($attendances > 0) {
            return trans_choice('It has :count check-in on record, so it is kept as history.|It has :count check-ins on record, so it is kept as history.', $attendances);
        }

        $raffles = $assembly->raffles()->count();

        if ($raffles > 0) {
            return trans_choice('It has :count raffle on record, so it is kept as history.|It has :count raffles on record, so it is kept as history.', $raffles);
        }

        return null;
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

        broadcast(new AssemblyChanged($assembly->id))->toOthers();
    }
}
