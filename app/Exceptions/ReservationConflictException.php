<?php

namespace App\Exceptions;

use RuntimeException;

// excepcion cuando la sala ya esta reservada en ese horario
class ReservationConflictException extends RuntimeException
{
    public function __construct(string $message = 'La sala ya está reservada en ese horario. Elige otro bloque.')
    {
        parent::__construct($message);
    }
}
