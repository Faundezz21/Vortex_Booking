<?php

namespace App\Actions\Reservations;

use App\Actions\Waitlist\NotifyWaitlist;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationCancelled;

// cancela una reserva y notifica al usuario y a la lista de espera
class CancelReservation
{
    public function __construct(private NotifyWaitlist $notifyWaitlist) {}

    public function handle(Reservation $reservation, User $cancelledBy): Reservation
    {
        $reservation->update([
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy->id,
        ]);

        $reservation->user->notify(new ReservationCancelled($reservation->fresh(['room', 'cancelledBy'])));
        $this->notifyWaitlist->handle($reservation, now());

        return $reservation;
    }
}
