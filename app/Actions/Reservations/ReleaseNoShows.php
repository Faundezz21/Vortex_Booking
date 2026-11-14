<?php

namespace App\Actions\Reservations;

use App\Actions\Waitlist\NotifyWaitlist;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Notifications\ReservationReleased;
use Illuminate\Support\Facades\DB;

// libera reservas confirmadas sin check in cuando vence el plazo de gracia
class ReleaseNoShows
{
    public function __construct(private NotifyWaitlist $notifyWaitlist) {}

    // retorna la cantidad de reservas liberadas
    public function handle(): int
    {
        $deadline = now()->subMinutes(config('booking.check_in.grace_minutes'));
        $released = 0;

        Reservation::query()
            ->confirmed()
            ->whereNull('checked_in_at')
            ->where('starts_at', '<=', $deadline)
            ->with(['room', 'user'])
            ->chunkById(100, function ($reservations) use (&$released) {
                foreach ($reservations as $reservation) {
                    // no se libera si el usuario realizo el check in justo antes
                    $updated = DB::table('reservations')
                        ->where('id', $reservation->id)
                        ->where('status', ReservationStatus::Confirmed->value)
                        ->whereNull('checked_in_at')
                        ->update(['status' => ReservationStatus::NoShow->value, 'released_at' => now(), 'updated_at' => now()]);

                    if (! $updated) {
                        continue;
                    }

                    $released++;
                    $reservation->refresh();

                    // notifica a la lista de espera si la reserva todavia no termina
                    if ($reservation->ends_at->isFuture()) {
                        $reservation->user->notify(new ReservationReleased($reservation));
                        $this->notifyWaitlist->handle($reservation, now());
                    }
                }
            });

        return $released;
    }
}
