<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $tz = config('app.display_timezone');
        $todayStart = now($tz)->startOfDay()->utc();
        $todayEnd = now($tz)->endOfDay()->utc();
        $opens = config('booking.check_in.opens_minutes_before');

        $data = [
            'upcoming' => $user->reservations()->with('room')->confirmed()->upcoming()->orderBy('starts_at')->limit(5)->get(),
            // reserva proxima o en curso pendiente de check in
            'current' => $user->reservations()->with('room')->confirmed()->whereNull('checked_in_at')
                ->where('starts_at', '<=', now()->addMinutes($opens))->where('ends_at', '>', now())
                ->orderBy('starts_at')->first(),
            'waitlist' => $user->waitlistEntries()->pending()->with('room')->orderBy('starts_at')->limit(3)->get(),
            'rooms' => Room::active()->orderBy('name')->get(),
            'maxActive' => config('booking.max_active_per_user'),
        ];

        if ($user->isAdmin()) {
            $last30 = Reservation::where('starts_at', '>=', now()->subDays(30))->where('starts_at', '<', now());
            $finished = (clone $last30)->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::NoShow])->count();
            $noShows = (clone $last30)->where('status', ReservationStatus::NoShow)->count();

            $data['stats'] = [
                ['label' => 'Reservas hoy', 'value' => Reservation::confirmed()->whereBetween('starts_at', [$todayStart, $todayEnd])->count()],
                ['label' => 'Próximas', 'value' => Reservation::confirmed()->upcoming()->count()],
                ['label' => 'Inasistencias', 'value' => $finished ? round($noShows / $finished * 100).'%' : '—', 'hint' => 'Últimos 30 días, liberadas automáticamente'],
                ['label' => 'Usuarios', 'value' => User::count()],
            ];
        }

        return view('dashboard', $data);
    }
}
