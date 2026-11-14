<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $start = now(config('app.display_timezone'))
            ->addDays(fake()->numberBetween(1, 14))
            ->setTime(fake()->numberBetween(8, 18), fake()->randomElement([0, 30]));

        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'starts_at' => $start->copy()->utc(),
            'ends_at' => $start->copy()->addMinutes(fake()->randomElement([30, 60, 90, 120]))->utc(),
            'status' => ReservationStatus::Confirmed,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /** Fija el intervalo a partir de horas locales (zona de visualización). */
    public function between(string $start, string $end): static
    {
        $tz = config('app.display_timezone');

        return $this->state(fn () => [
            'starts_at' => Carbon::parse($start, $tz)->utc(),
            'ends_at' => Carbon::parse($end, $tz)->utc(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $attributes['user_id'],
        ]);
    }
}
