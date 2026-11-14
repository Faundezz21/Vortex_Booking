<?php

namespace App\Enums;

enum AmenityKind: string
{
    // instalado en la sala
    case Equipment = 'equipment';
    // se retira en el punto de entrega con la reserva
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Equipment => 'Equipamiento',
            self::Pickup => 'Se retira con la reserva',
        };
    }
}
