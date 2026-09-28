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
 * Someone checked in or out at the assembly (or a record was voided or
 * undone), so the panel and the desks read counters and quorum again.
 *
 * Broadcast right away, without the queue, so a stopped worker never leaves
 * the desks behind during the assembly; a broadcasting failure is reported
 * but never undoes the check-in.
 */
class AttendanceChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
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
