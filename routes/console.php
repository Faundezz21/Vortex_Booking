<?php

use App\Actions\Reservations\ReleaseNoShows;
use App\Models\Room;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('reservations:release-no-shows', function (ReleaseNoShows $release) {
    $count = $release->handle();
    $this->info($count === 1 ? '1 reserva liberada.' : "{$count} reservas liberadas.");
})->purpose('libera reservas sin check in cuando vence el plazo de gracia');

Schedule::command('reservations:release-no-shows')->everyMinute()->withoutOverlapping();

Artisan::command('demo:seed {--if-empty : Solo carga datos si no hay salas} {--fresh : Borra todo y vuelve a cargar}', function () {
    if ($this->option('fresh')) {
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        return;
    }

    if ($this->option('if-empty') && Room::withTrashed()->exists()) {
        $this->info('Ya hay datos; no se cargan datos de demostración.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
})->purpose('carga datos iniciales de salas usuarios y reservas');
