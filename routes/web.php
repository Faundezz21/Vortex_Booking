<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CalendarExportController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use App\Livewire\Calendar;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', HomeController::class)->name('home');

// feed de calendario personal accesible mediante token
Route::get('calendario/feed/{token}.ics', [CalendarExportController::class, 'feed'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:60,1')
    ->name('calendar.feed');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('calendario', Calendar::class)->name('calendar');

    // exploracion y reserva de salas
    Route::get('salas', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('salas/{room}', [RoomController::class, 'show'])->name('rooms.show');

    Route::get('mis-reservas', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservas/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('reservas/{reservation}/ics', [CalendarExportController::class, 'download'])->name('reservations.ics');
    Route::post('reservas', [ReservationController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('reservations.store');
    Route::patch('reservas/{reservation}/cancelar', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    // registro de asistencia mediante qr
    Route::get('check-in/{room}', [CheckInController::class, 'show'])->name('check-in.room');
    Route::post('check-in/{room}', [CheckInController::class, 'store'])->middleware('throttle:10,1')->name('check-in.store');

    // modulo de administracion de salas y reservas
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('salas', Admin\RoomController::class)
            ->except('show')
            ->parameters(['salas' => 'room'])
            ->names('rooms');
        Route::get('salas/{room}/qr', [Admin\RoomQrController::class, 'show'])->name('rooms.qr');
        Route::post('salas/{room}/qr', [Admin\RoomQrController::class, 'regenerate'])->name('rooms.qr.regenerate');
        Route::get('reservas', [Admin\ReservationController::class, 'index'])->name('reservations.index');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Route::get('settings/calendario', [CalendarExportController::class, 'settings'])->name('settings.calendar');
    Route::post('settings/calendario', [CalendarExportController::class, 'regenerate'])->name('settings.calendar.regenerate');
});

require __DIR__.'/auth.php';
