<?php

namespace App\Enums;

/**
 * The three posts of an assembly: whoever runs it, whoever receives people at
 * the door and the projector the room looks at.
 */
enum Role: string
{
    case Admin = 'admin';
    case Registrar = 'registrar';
    case Projector = 'projector';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Administrator'),
            self::Registrar => __('Registrar'),
            self::Projector => __('Projector'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => __('Full access: roll, assemblies, raffles and users.'),
            self::Registrar => __('Only the registration desk: check-ins and check-outs.'),
            self::Projector => __('Only the projection screen.'),
        };
    }

    /**
     * Flux badge color used to tell roles apart at a glance.
     */
    public function color(): string
    {
        return match ($this) {
            self::Admin => 'amber',
            self::Registrar => 'emerald',
            self::Projector => 'sky',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Registrar => [Permission::RegisterAttendance],
            self::Projector => [Permission::ViewScreen],
        };
    }
}
