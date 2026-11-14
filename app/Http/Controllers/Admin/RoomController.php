<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $rooms = Room::query()
            ->withCount(['reservations as upcoming_count' => fn ($q) => $q->confirmed()->upcoming()])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('location', "%{$search}%")))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.rooms.index', compact('rooms', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', Room::class);

        return view('admin.rooms.form', ['amenities' => Amenity::ordered()->get(), 'room' => new Room([
            'opens_at' => '08:00',
            'closes_at' => '21:00',
            'max_duration_minutes' => 120,
            'is_active' => true,
        ])]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $room = Room::create($request->roomData());
        $room->amenities()->sync($request->amenityIds());

        return to_route('admin.rooms.index')->with('status', "Sala «{$room->name}» creada.");
    }

    public function edit(Room $room): View
    {
        Gate::authorize('update', $room);

        return view('admin.rooms.form', ['room' => $room->load('amenities'), 'amenities' => Amenity::ordered()->get()]);
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->roomData());
        $room->amenities()->sync($request->amenityIds());

        return to_route('admin.rooms.index')->with('status', "Sala «{$room->name}» actualizada.");
    }

    // soft delete de la sala validando que no tenga reservas futuras
    public function destroy(Room $room): RedirectResponse
    {
        Gate::authorize('delete', $room);

        $upcoming = $room->reservations()->confirmed()->upcoming()->count();
        if ($upcoming > 0) {
            return back()->with('error', "No se puede eliminar «{$room->name}»: tiene {$upcoming} reservas futuras. Desactívala o cancela esas reservas primero.");
        }

        $room->delete();

        return to_route('admin.rooms.index')->with('status', "Sala «{$room->name}» eliminada.");
    }
}
