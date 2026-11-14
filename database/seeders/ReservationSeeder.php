<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

// genera reservas de prueba respetando limites de horario y cupos
class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $tz = config('app.display_timezone');
        $users = User::where('role', Role::User)->get();
        $demoUser = $users->firstWhere('email', 'usuario@vortexbooking.test');
        $notes = [null, null, 'Estudio para certamen', 'Trabajo grupal', 'Preparación de presentación', 'Repaso de cálculo'];
        $max = config('booking.max_active_per_user');
        $active = [];

        foreach (Room::active()->get() as $room) {
            // genera datos desde tres dias atras hasta diez dias en adelante
            for ($offset = -3; $offset <= 10; $offset++) {
                $day = now($tz)->startOfDay()->addDays($offset);

                if ($day->isSunday()) {
                    continue;
                }

                $cursor = $day->copy()->setTimeFromTimeString($room->opens_at);
                $closing = $day->copy()->setTimeFromTimeString($room->closes_at);

                while (true) {
                    // espacio libre aleatorio antes de la siguiente reserva
                    $cursor->addMinutes(fake()->randomElement([0, 30, 60, 90, 120, 180]));
                    $duration = fake()->randomElement(array_filter(
                        [30, 60, 90, 120, 180],
                        fn (int $m) => $m <= $room->max_duration_minutes,
                    ));
                    $end = $cursor->copy()->addMinutes($duration);

                    if ($end->gt($closing)) {
                        break;
                    }

                    $cancelled = fake()->boolean(10);
                    $isUpcoming = $end->isFuture();

                    // respeta el limite maximo de reservas activas por usuario
                    $candidates = $users->filter(fn (User $u) => ! $isUpcoming || $cancelled
                        || ($active[$u->id] ?? 0) < ($u->is($demoUser) ? $max - 1 : $max));

                    if ($candidates->isEmpty()) {
                        $cursor = $end;

                        continue;
                    }

                    $user = $candidates->contains($demoUser) && fake()->boolean(15) ? $demoUser : $candidates->random();

                    if ($isUpcoming && ! $cancelled) {
                        $active[$user->id] = ($active[$user->id] ?? 0) + 1;
                    }

                    // simula check in o inasistencia en reservas pasadas
                    $started = $cursor->isPast();
                    $attended = $started && ! $cancelled && fake()->boolean(85);
                    $noShow = $started && ! $cancelled && ! $attended
                        && $cursor->copy()->addMinutes(config('booking.check_in.grace_minutes'))->isPast();

                    Reservation::create([
                        'user_id' => $user->id,
                        'room_id' => $room->id,
                        'starts_at' => $cursor->copy()->utc(),
                        'ends_at' => $end->copy()->utc(),
                        'status' => match (true) {
                            $cancelled => ReservationStatus::Cancelled,
                            $noShow => ReservationStatus::NoShow,
                            default => ReservationStatus::Confirmed,
                        },
                        'notes' => fake()->randomElement($notes),
                        'cancelled_at' => $cancelled ? Carbon::now() : null,
                        'cancelled_by' => $cancelled ? $user->id : null,
                        'checked_in_at' => $attended ? $cursor->copy()->addMinutes(fake()->numberBetween(0, 10))->utc() : null,
                        'released_at' => $noShow ? $cursor->copy()->addMinutes(config('booking.check_in.grace_minutes'))->utc() : null,
                    ]);

                    $cursor = $end;
                }
            }
        }

        // agrega usuarios de prueba en lista de espera para bloques ocupados
        Reservation::confirmed()->where('starts_at', '>', now()->addDay())->inRandomOrder()->limit(12)->get()
            ->each(function (Reservation $r) use ($users) {
                $waiter = $users->where('id', '!=', $r->user_id)->random();
                WaitlistEntry::firstOrCreate(
                    ['user_id' => $waiter->id, 'room_id' => $r->room_id, 'starts_at' => $r->starts_at],
                    ['ends_at' => $r->starts_at->copy()->addMinutes(config('booking.slot_minutes'))],
                );
            });
    }
}
