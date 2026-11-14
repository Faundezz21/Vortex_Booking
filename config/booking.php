<?php

return [

    // Granularidad de las reservas, en minutos. Inicio y duración deben ser múltiplos de este valor.
    'slot_minutes' => 30,

    // Reservas futuras confirmadas que puede tener un usuario al mismo tiempo.
    'max_active_per_user' => (int) env('BOOKING_MAX_ACTIVE_PER_USER', 3),

    // Cuántos días hacia adelante se puede reservar.
    'max_days_ahead' => (int) env('BOOKING_MAX_DAYS_AHEAD', 14),

    // Check-in escaneando el QR de la sala. Sin check-in dentro del plazo de gracia,
    // la reserva se marca como "no asistió" y el bloque se libera para la lista de espera.
    'check_in' => [
        'opens_minutes_before' => 10,
        'grace_minutes' => (int) env('BOOKING_CHECK_IN_GRACE', 15),
    ],

];
