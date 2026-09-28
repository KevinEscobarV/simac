<?php

namespace App\Concerns;

use Closure;
use Illuminate\Validation\ValidationException;

/**
 * For the Livewire components that run actions: why an action could not go
 * ahead has to stay on screen.
 */
trait ReportsOnProperty
{
    /**
     * Run an action and report why it could not go ahead under a property of
     * the component (a search field, a form), where the page shows it. The
     * actions report on keys that are not properties (key, teacher,
     * participants…), and Livewire forgets those on the next request, such
     * as the debounced sync of a field that follows an Enter.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $action
     * @return TResult
     *
     * @throws ValidationException
     */
    protected function reportingOn(string $property, Closure $action): mixed
    {
        try {
            return $action();
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                $property => $exception->validator->errors()->first(),
            ]);
        }
    }
}
