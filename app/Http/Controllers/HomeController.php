<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\View\View;

class HomeController extends Controller
{
    // portada con la ocupacion de hoy en linea de tiempo
    public function __invoke(): View
    {
        $tz = config('app.display_timezone');
        $start = now($tz)->startOfDay()->setTime(8, 0);
        $end = now($tz)->startOfDay()->setTime(22, 0);
        $total = $start->diffInMinutes($end);
        $pct = fn ($t) => max(0, min(100, $start->diffInMinutes($t->copy()->setTimezone($tz), false) / $total * 100));

        $rooms = Room::active()->orderBy('name')->get();
        $reservations = Reservation::confirmed()
            ->whereIn('room_id', $rooms->pluck('id'))
            ->where('starts_at', '<', $end->copy()->utc())
            ->where('ends_at', '>', $start->copy()->utc())
            ->get();

        $timeline = $rooms->map(fn (Room $room) => [
            'room' => $room,
            'bars' => $reservations->where('room_id', $room->id)->map(fn ($r) => [
                'left' => $pct($r->starts_at),
                'width' => $pct($r->ends_at) - $pct($r->starts_at),
                'label' => $r->timeRangeLabel(),
            ])->values(),
        ]);

        $now = now($tz);

        return view('welcome', [
            'timeline' => $timeline,
            'nowLeft' => $now->between($start, $end) ? $pct($now) : null,
            'hours' => range(8, 22, 2),
        ]);
    }
}
