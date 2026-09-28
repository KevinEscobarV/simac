<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An assembly was opened, closed, reopened or deleted, or its quorum was
 * adjusted: a desk learns on its own that there is nothing left to register.
 *
 * Broadcast like {@see AttendanceChanged}: right away and without letting a
 * broadcasting failure undo the change.
 */
class AssemblyChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $assemblyId) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('assemblies'),
        ];
    }
}
