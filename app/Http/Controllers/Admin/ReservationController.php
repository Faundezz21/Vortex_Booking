<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'room' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(ReservationStatus::class)],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $tz = config('app.display_timezone');

        $reservations = Reservation::query()
            ->with(['room', 'user', 'cancelledBy'])
            ->when($filters['room'] ?? null, fn ($q, $room) => $q->where('room_id', $room))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['date'] ?? null, function ($q, $date) use ($tz) {
                $day = Carbon::createFromFormat('Y-m-d', $date, $tz)->startOfDay();
                $q->where('starts_at', '>=', $day->copy()->utc())->where('starts_at', '<', $day->copy()->addDay()->utc());
            }, fn ($q) => $q->upcoming())
            ->orderBy('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'rooms' => Room::withTrashed()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }
}
