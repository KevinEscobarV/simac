<?php

namespace App\Actions\Attendance;

use App\Models\Teacher;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResolveTeacher
{
    /**
     * Find the teacher standing at the desk from what was typed or scanned.
     * The exact keys go first, the code before the ID number (both are
     * digits), so a barcode reader never depends on a text search; then the
     * text has to match exactly one active teacher.
     *
     * @throws ValidationException when nobody or more than one teacher matches
     */
    public function handle(string $key): Teacher
    {
        $key = Str::squish($key);

        if ($key === '') {
            throw ValidationException::withMessages([
                'key' => __('Type the code, the ID number or the name of the teacher.'),
            ]);
        }

        $exact = $this->exactMatch($key);

        if ($exact !== null) {
            return $exact;
        }

        $matches = Teacher::query()->search($key)->count();

        if ($matches === 1) {
            return Teacher::query()->search($key)->sole();
        }

        throw ValidationException::withMessages([
            'key' => $matches === 0
                ? __('No teacher matches “:key”.', ['key' => $key])
                : __(':count teachers match “:key”: pick one from the list.', ['count' => $matches, 'key' => $key]),
        ]);
    }

    private function exactMatch(string $key): ?Teacher
    {
        if (preg_match('/^[\d.\s-]+$/', $key) !== 1) {
            return null;
        }

        return Teacher::firstWhere('code', $key)
            ?? Teacher::firstWhere('document_number', preg_replace('/\D/', '', $key));
    }
}
