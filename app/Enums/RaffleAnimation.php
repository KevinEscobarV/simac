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
            self::Drum => __('Raffle drum'),
            self::Reveal => __('Countdown reveal'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Wheel => __('A wheel with the participants spins and slows down on the winner.'),
            self::Drum => __('The drum turns and a ball with the winner comes out.'),
            self::Reveal => __('A countdown, then the name appears letter by letter.'),
        };
    }
}
