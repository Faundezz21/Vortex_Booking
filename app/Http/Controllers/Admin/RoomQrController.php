<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// afiche con el qr de check in para la puerta de la sala
class RoomQrController extends Controller
{
    public function show(Room $room): View
    {
        $svg = (new QRCode(new QROptions([
            'outputType' => 'svg',
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
            'addQuietzone' => true,
        ])))->render($room->checkInUrl());

        return view('admin.rooms.qr', ['room' => $room, 'qrSvg' => $svg]);
    }

    public function regenerate(Room $room): RedirectResponse
    {
        $room->regenerateCheckInToken();

        return back()->with('status', 'Generamos un QR nuevo para '.$room->name.'. Imprímelo y reemplaza el de la puerta: el anterior ya no funciona.');
    }
}
