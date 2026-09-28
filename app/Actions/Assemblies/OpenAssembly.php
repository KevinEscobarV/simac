<?php

namespace App\Actions\Assemblies;

use App\Enums\QuorumType;
use App\Events\AssemblyChanged;
use App\Models\Assembly;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OpenAssembly
{
    /**
     * Open an assembly. Only one can be open at a time, otherwise "who is
     * present" would be ambiguous; the lock keeps two admins from opening
     * one each at the same instant.
     *
     * @param  array{name: string, date: string, location: string|null, quorum_type: QuorumType|null, quorum_value: float|null}  $attributes
     *
     * @throws ValidationException when another assembly is still open
     */
    public function handle(User $openedBy, array $attributes): Assembly
    {
        $assembly = Cache::lock(Assembly::OPENING_LOCK, 10)->block(5, function () use ($openedBy, $attributes): Assembly {
            $open = Assembly::current();

            if ($open !== null) {
                throw ValidationException::withMessages([
                    'assembly' => __('":name" is still open. Close it before opening another one.', ['name' => $open->name]),
                ]);
            }

            return Assembly::create([...$attributes, 'opened_by' => $openedBy->id]);
        });

        broadcast(new AssemblyChanged($assembly->id))->toOthers();

        return $assembly;
    }
}
