<?php

namespace App\Events;

use App\Enums\ProjectionPhase;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The projection changed phase, or a screen was turned on. Screens and the
 * console read the state again; a new attempt restarts the animation.
 *
 * It carries no names: who won is only rendered by the server, when the
 * phase lets it be shown.
 */
class ProjectionUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public ProjectionPhase $phase,
        public int $attempt,
        public ?int $raffleId,
        public int $winnerPosition,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('projection'),
        ];
    }
}
