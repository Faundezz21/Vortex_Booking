<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Sala '.fake()->unique()->bothify('##?');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(12),
            'location' => fake()->randomElement(['Biblioteca Central', 'Edificio de Ingeniería', 'Campus Norte']).', piso '.fake()->numberBetween(1, 4),
            'capacity' => fake()->randomElement([2, 4, 6, 8, 12]),
            'opens_at' => '08:00:00',
            'closes_at' => '21:00:00',
            'max_duration_minutes' => 120,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
