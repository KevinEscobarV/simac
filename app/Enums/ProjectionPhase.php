<?php

namespace App\Enums;

/**
 * What the projection screen is showing. The state lives on the server, so a
 * screen turned on late, reloaded, or a second one, all show the same.
 */
enum ProjectionPhase: string
{
    /** Nothing to show: the screen rests. */
    case Idle = 'idle';

    /** A raffle is loaded and the room gets ready ("GET READY"). */
    case Ready = 'ready';

    /** The animation of the current winner is running. */
    case Animating = 'animating';

    /** The animation ended and the current winner is on screen. */
    case Winner = 'winner';
}
