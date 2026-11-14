<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;

// registra la asistencia del usuario en una sala con el qr de la puerta
class CheckIn
{
    // busca la reserva del usuario dentro de la ventana de check in
    public function findReservation(User $user, Room $room): ?Reservation
    {
        $opens = config('booking.check_in.opens_minutes_before');

        return $user->reservations()
            ->confirmed()
            ->where('room_id', $room->id)
            ->where('starts_at', '<=', now()->addMinutes($opens))
            ->where('ends_at', '>', now())
            ->orderBy('starts_at')
            ->first();
    }

    public function handle(Reservation $reservation): bool
    {
        if (! $reservation->canCheckInNow()) {
            return false;
        }

        return (bool) Reservation::whereKey($reservation->id)
            ->confirmed()
            ->whereNull('checked_in_at')
            ->update(['checked_in_at' => now()]);
    }
}
