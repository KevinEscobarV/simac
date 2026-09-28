<?php

namespace App\Actions\Locations;

use App\Models\City;
use Illuminate\Validation\ValidationException;

class DeleteCity
{
    /**
     * Why the city cannot be deleted yet, or null when nothing depends on it.
     */
    public function blocker(City $city): ?string
    {
        $schools = $city->schools()->count();

        if ($schools === 0) {
            return null;
        }

        return trans_choice('It still has :count school. Move or delete it first.|It still has :count schools. Move or delete them first.', $schools);
    }

    /**
     * Delete a city that no longer has schools. The database would refuse it
     * anyway; checking first lets the user know what is still left.
     *
     * @throws ValidationException
     */
    public function handle(City $city): void
    {
        $blocker = $this->blocker($city);

        if ($blocker !== null) {
            throw ValidationException::withMessages(['city' => $blocker]);
        }

        $city->delete();
    }
}
