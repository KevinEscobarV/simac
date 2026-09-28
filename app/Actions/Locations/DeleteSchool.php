<?php

namespace App\Actions\Locations;

use App\Models\School;
use Illuminate\Validation\ValidationException;

class DeleteSchool
{
    /**
     * Why the school cannot be deleted yet, or null when nothing depends on
     * it. Retired teachers count too: their history still points here.
     */
    public function blocker(School $school): ?string
    {
        $teachers = $school->teachers()->withTrashed()->count();

        if ($teachers === 0) {
            return null;
        }

        if ($school->teachers()->onlyTrashed()->doesntExist()) {
            return trans_choice('It still has :count teacher. Move them to another school first.|It still has :count teachers. Move them to another school first.', $teachers);
        }

        return trans_choice('It has :count teacher on record, counting retired ones, so it is kept.|It has :count teachers on record, counting retired ones, so it is kept.', $teachers);
    }

    /**
     * Delete a school that no teacher belongs to.
     *
     * @throws ValidationException
     */
    public function handle(School $school): void
    {
        $blocker = $this->blocker($school);

        if ($blocker !== null) {
            throw ValidationException::withMessages(['school' => $blocker]);
        }

        $school->delete();
    }
}
