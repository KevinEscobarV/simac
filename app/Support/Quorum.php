<?php

namespace App\Support;

use App\Enums\QuorumType;

/**
 * The quorum of an assembly at a given moment.
 *
 * It is measured over the union members, not the whole roll, and counts the
 * members present right now: if people leave, the quorum is lost, and that is
 * exactly what has to be visible.
 */
final readonly class Quorum
{
    public function __construct(
        public QuorumType $type,
        public float $value,
        public int $unionMembers,
        public int $presentMembers,
    ) {}

    /**
     * Members needed, rounded up: 50 % of 13 members is 7, not 6.5. A number
     * above the roll is not capped, so an unreachable quorum shows as such.
     */
    public static function requiredFor(QuorumType $type, float $value, int $unionMembers): int
    {
        $required = match ($type) {
            QuorumType::Count => $value,
            QuorumType::Percentage => $unionMembers * $value / 100,
        };

        // Rounding first keeps float noise (10 × 70 % = 7.000000000000001) from adding a member.
        return (int) ceil(round($required, 6));
    }

    public function required(): int
    {
        return self::requiredFor($this->type, $this->value, $this->unionMembers);
    }

    public function missing(): int
    {
        return max(0, $this->required() - $this->presentMembers);
    }

    public function isMet(): bool
    {
        return $this->presentMembers >= $this->required();
    }

    public function isReachable(): bool
    {
        return $this->required() <= $this->unionMembers;
    }

    /**
     * How far along it is, from 0 to 1. Going past the quorum does not
     * lengthen the bar.
     */
    public function progress(): float
    {
        $required = $this->required();

        return $required > 0 ? min(1, $this->presentMembers / $required) : 1;
    }
}
