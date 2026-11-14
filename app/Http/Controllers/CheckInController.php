<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CheckIn;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// gestiona la pantalla y el registro de asistencia mediante qr
class CheckInController extends Controller
{
    public function show(Request $request, Room $room, CheckIn $checkIn): View
    {
        $this->ensureValidToken($request, $room);

        return view('check-in.show', [
            'room' => $room,
            'token' => $request->query('k'),
            'reservation' => $checkIn->findReservation($request->user(), $room),
        ]);
    }

    public function store(Request $request, Room $room, CheckIn $checkIn): RedirectResponse
    {
        $this->ensureValidToken($request, $room);

        $reservation = $checkIn->findReservation($request->user(), $room);

        if ($reservation && ! $reservation->canCheckInNow() && $reservation->starts_at->isFuture()) {
            return back()->with('error', 'Aún es temprano: el check-in se abre a las '.$reservation->checkInOpensAt()->setTimezone(config('app.display_timezone'))->format('H:i').'.');
        }

        if (! $reservation || ! $checkIn->handle($reservation)) {
            return back()->with('error', 'No pudimos registrar tu asistencia. Revisa el horario de tu reserva.');
        }

        return to_route('reservations.show', $reservation)
            ->with('status', '¡Listo! Registramos tu llegada a '.$room->name.'. La sala es tuya hasta las '.$reservation->localEnd()->format('H:i').'.');
    }

    private function ensureValidToken(Request $request, Room $room): void
    {
        abort_unless(
            is_string($request->input('k')) && hash_equals((string) $room->check_in_token, $request->input('k')),
            404,
            'Este código QR no es válido o fue reemplazado.',
        );
    }
}
