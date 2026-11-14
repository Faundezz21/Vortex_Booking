<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CancelReservation;
use App\Actions\Reservations\CreateReservation;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('reservations.index', [
            'upcoming' => $user->reservations()->with('room')->confirmed()->upcoming()->orderBy('starts_at')->get(),
            'history' => $user->reservations()->with(['room', 'cancelledBy'])
                ->where(fn ($q) => $q->where('ends_at', '<=', now())->orWhere('status', 'cancelled'))
                ->latest('starts_at')
                ->paginate(10),
            'maxActive' => config('booking.max_active_per_user'),
        ]);
    }

    public function show(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);

        return view('reservations.show', [
            'reservation' => $reservation->load(['room.amenities', 'user', 'cancelledBy']),
        ]);
    }

    public function store(StoreReservationRequest $request, CreateReservation $createReservation): RedirectResponse
    {
        try {
            $reservation = $createReservation->handle(
                $request->user(),
                $request->room(),
                $request->startsAt(),
                $request->endsAt(),
                $request->validated('notes'),
            );
        } catch (ReservationConflictException $e) {
            // otra persona tomo el horario entre la validacion y la transaccion
            return back()->withInput()->withErrors(['start_time' => $e->getMessage()]);
        }

        return to_route('reservations.show', $reservation)->with(
            'status',
            "Reserva confirmada: {$reservation->room->name}, {$reservation->localStart()->translatedFormat('l j \\d\\e F')} de {$reservation->timeRangeLabel()}. Te enviamos un correo con el detalle.",
        );
    }

    public function cancel(Request $request, Reservation $reservation, CancelReservation $cancelReservation): RedirectResponse
    {
        $response = Gate::inspect('cancel', $reservation);
        if ($response->status() === 404) {
            abort(404);
        }
        if ($response->denied()) {
            return back()->with('error', $response->message());
        }

        $cancelReservation->handle($reservation, $request->user());

        return back()->with('status', "Reserva de {$reservation->room->name} ({$reservation->timeRangeLabel()}) cancelada.");
    }
}
