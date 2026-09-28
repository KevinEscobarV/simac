<?php

namespace App\Concerns;

use Closure;
use Illuminate\Validation\ValidationException;

/**
 * For the Livewire components that take attendance from a search field.
 */
trait ReportsOnSearch
{
    /**
     * Run an attendance action and report why it could not go ahead on the
     * search field, where the page shows it. The actions report on keys that
     * are not properties (key, teacher, assembly), and Livewire forgets those
     * on the next request, such as the debounced sync of the field that
     * follows an Enter.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $action
     * @return TResult
     *
     * @throws ValidationException
     */
    protected function reportingOnSearch(Closure $action): mixed
    {
        try {
            return $action();
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'search' => $exception->validator->errors()->first(),
            ]);
        }
    }
}
