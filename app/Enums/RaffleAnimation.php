<?php

namespace App\Enums;

/**
 * How the screen presents a result that is already sealed in the record: the
 * animation only shows it, it never chooses it.
 */
enum RaffleAnimation: string
{
    case Wheel = 'wheel';
    case Drum = 'drum';
    case Reveal = 'reveal';

    public function label(): string
    {
        return match ($this) {
            self::Wheel => __('Wheel'),
            self::Drum => __('Name drum'),
            self::Reveal => __('Countdown'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Wheel => __('The wheel spins and slows down until it stops on the winning name. The classic.'),
            self::Drum => __('The names go by fast and slow down little by little. Best for long lists.'),
            self::Reveal => __('3, 2, 1… and the name appears letter by letter. Quick and effective.'),
        };
    }
}
