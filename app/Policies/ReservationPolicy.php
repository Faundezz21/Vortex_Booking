<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    // autoriza ver detalle o descargar ics al propietario o admin
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin() || $reservation->user_id === $user->id;
    }

    // autoriza cancelacion al usuario antes de iniciar o al admin antes de terminar
    public function cancel(User $user, Reservation $reservation): Response
    {
        if ($reservation->isCancelled()) {
            return Response::deny('Esta reserva ya estaba cancelada.');
        }

        if ($reservation->isNoShow()) {
            return Response::deny('Esta reserva se liberó porque no se registró asistencia.');
        }

        if ($user->isAdmin()) {
            return $reservation->ends_at->isPast()
                ? Response::deny('No se puede cancelar una reserva que ya terminó.')
                : Response::allow();
        }

        if ($reservation->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return $reservation->hasStarted()
            ? Response::deny('No puedes cancelar una reserva que ya comenzó.')
            : Response::allow();
    }
}
