<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use App\Support\IcsCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CalendarExportController extends Controller
{
    // feed de calendario personal protegido por token para suscripcion
    public function feed(string $token): Response
    {
        $user = User::where('calendar_token', $token)->firstOrFail();

        $reservations = $user->reservations()
            ->with('room')
            ->where('starts_at', '>=', now()->subDays(30))
            ->orderBy('starts_at')
            ->get();

        return $this->ics(IcsCalendar::build($reservations, 'Vortex Booking · '.$user->name), 'vortex-booking.ics', inline: true);
    }

    public function download(Reservation $reservation): Response
    {
        Gate::authorize('view', $reservation);

        $reservation->load('room');

        return $this->ics(IcsCalendar::build([$reservation], 'Vortex Booking'), 'reserva-'.$reservation->id.'.ics');
    }

    public function settings(Request $request): View
    {
        return view('settings.calendar', ['feedUrl' => $request->user()->calendarFeedUrl()]);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $request->user()->regenerateCalendarToken();

        return back()->with('status', 'Generamos un enlace nuevo. El anterior dejó de funcionar.');
    }

    private function ics(string $body, string $filename, bool $inline = false): Response
    {
        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
