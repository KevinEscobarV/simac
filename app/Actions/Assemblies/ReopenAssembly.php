<?php

namespace App\Actions\Assemblies;

use App\Models\Assembly;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ReopenAssembly
{
    /**
     * Reopen a closed assembly, as long as no other one is open.
     *
     * @throws ValidationException when another assembly is open
     */
    public function handle(Assembly $assembly): void
    {
        Cache::lock(Assembly::OPENING_LOCK, 10)->block(5, function () use ($assembly): void {
            $open = Assembly::current();

            if ($open !== null && $open->isNot($assembly)) {
                throw ValidationException::withMessages([
                    'assembly' => __('":name" is open. Close it before reopening another one.', ['name' => $open->name]),
                ]);
            }

            $assembly->forceFill(['closed_at' => null])->save();
        });
    }
}
