<?php

namespace App\Enums;

/**
 * How an assembly states the union members it needs in the room.
 */
enum QuorumType: string
{
    /** "7 members are required". */
    case Count = 'count';

    /** "50 % of the members": computed on the fly, so it follows the roll. */
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Count => __('Number'),
            self::Percentage => __('Percentage'),
        };
    }
}
