<?php

namespace App\Http\Controllers;

use App\Enums\AmenityKind;
use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $minCapacity = max(0, $request->integer('capacity'));
        $amenity = (string) $request->query('con');

        $rooms = Room::active()
            ->with('amenities')
            ->when($minCapacity > 0, fn ($q) => $q->where('capacity', '>=', $minCapacity))
            ->when($amenity !== '', fn ($q) => $q->whereHas('amenities', fn ($q) => $q->where('slug', $amenity)))
            ->orderBy('name')
            ->get();

        return view('rooms.index', [
            'rooms' => $rooms,
            'minCapacity' => $minCapacity,
            'amenity' => $amenity,
            'equipment' => Amenity::where('kind', AmenityKind::Equipment)->ordered()->get(),
        ]);
    }

    public function show(Room $room): View
    {
        Gate::authorize('view', $room);

        return view('rooms.show', ['room' => $room->load('amenities')]);
    }
}
