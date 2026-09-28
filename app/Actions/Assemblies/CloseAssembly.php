<?php

namespace App\Actions\Assemblies;

use App\Models\Assembly;

class CloseAssembly
{
    /**
     * Close the assembly. Whoever had not checked out stays as present in its
     * history.
     */
    public function handle(Assembly $assembly): void
    {
        if ($assembly->isOpen()) {
            $assembly->forceFill(['closed_at' => now()])->save();
        }
    }
}
