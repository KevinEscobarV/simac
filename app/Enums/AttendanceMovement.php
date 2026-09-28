<?php

namespace App\Enums;

/**
 * What happened when someone was registered. At the desk it is not the same
 * to welcome a teacher, to find out they were already in, or to let them back
 * in: whoever is at the door needs to know which one it was.
 */
enum AttendanceMovement: string
{
    case CheckIn = 'check-in';

    /** They were already present: nothing changed. */
    case AlreadyPresent = 'already-present';

    /** They had left and came back: the original check-in time is kept. */
    case Reentry = 'reentry';

    case CheckOut = 'check-out';

    /**
     * Whether it changed anything that can be undone.
     */
    public function isUndoable(): bool
    {
        return $this !== self::AlreadyPresent;
    }
}
