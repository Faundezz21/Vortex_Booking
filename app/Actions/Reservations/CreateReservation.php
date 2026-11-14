<?php

namespace App\Actions\Reservations;

use App\Exceptions\ReservationConflictException;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ReservationConfirmed;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// crea una reserva asegurando que no existan cruces de horario
class CreateReservation
{
    private const EXCLUSION_VIOLATION = '23P01';

    // lanza excepcion si existe conflicto de horario
    public function handle(User $user, Room $room, CarbonInterface $startsAt, CarbonInterface $endsAt, ?string $notes = null): Reservation
    {
        try {
            $reservation = DB::transaction(function () use ($user, $room, $startsAt, $endsAt, $notes) {
                Room::whereKey($room->id)->lockForUpdate()->first();

                if (Reservation::overlapping($room->id, $startsAt, $endsAt)->exists()) {
                    throw new ReservationConflictException;
                }

                return $user->reservations()->create([
                    'room_id' => $room->id,
                    'starts_at' => $startsAt->copy()->utc(),
                    'ends_at' => $endsAt->copy()->utc(),
                    'notes' => $notes,
                ]);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === self::EXCLUSION_VIOLATION) {
                throw new ReservationConflictException;
            }

            throw $e;
        }

        $user->notify(new ReservationConfirmed($reservation));

        return $reservation;
    }
}
