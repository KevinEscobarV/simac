<?php

namespace App\Actions\Assemblies;

use App\Enums\QuorumType;
use App\Events\AssemblyChanged;
use App\Models\Assembly;

class AdjustQuorum
{
    /**
     * Change the quorum without closing the assembly: the figure may arrive
     * after it started, or change on the way.
     *
     * @param  array{quorum_type: QuorumType|null, quorum_value: float|null}  $quorum
     */
    public function handle(Assembly $assembly, array $quorum): void
    {
        $assembly->update($quorum);

        broadcast(new AssemblyChanged($assembly->id))->toOthers();
    }
}
