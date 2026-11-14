<?php

namespace App\Support;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// calcula los bloques horarios de una sala y su estado para un dia
class RoomSchedule
{
    /**
     * @return Collection<int, array{time: string, state: string}>
     */
    public static function day(Room $room, CarbonInterface $day, ?int $userId = null): Collection
    {
        $tz = config('app.display_timezone');
        $day = Carbon::instance($day)->setTimezone($tz)->startOfDay();
        $open = $room->opensAtOn($day);
        $close = $room->closesAtOn($day);
        $step = config('booking.slot_minutes');

        $reservations = Reservation::confirmed()
            ->where('room_id', $room->id)
            ->where('starts_at', '<', $close->copy()->utc())
            ->where('ends_at', '>', $open->copy()->utc())
            ->get(['user_id', 'starts_at', 'ends_at']);

        $slots = collect();
        for ($t = $open->copy(); $t->lt($close); $t->addMinutes($step)) {
            $slotEnd = $t->copy()->addMinutes($step);
            $hit = $reservations->first(fn ($r) => $r->starts_at->lt($slotEnd) && $r->ends_at->gt($t));

            $state = match (true) {
                $hit !== null && $userId !== null && $hit->user_id === $userId => 'mine',
                $hit !== null => 'busy',
                $t->isPast() => 'past',
                default => 'free',
            };

            $slots->push(['time' => $t->format('H:i'), 'state' => $state]);
        }

        return $slots;
    }

    // retorna el primer horario disponible para el dia actual
    public static function nextFreeToday(Room $room): ?string
    {
        return self::day($room, now())->firstWhere('state', 'free')['time'] ?? null;
    }
}
