<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $biblioteca = 'Mesón de préstamo, Biblioteca Central piso 1';

        // definicion de salas con ubicacion capacidad horario color y comodidades
        $rooms = [
            ['Sala Individual A1', 'Biblioteca Central, piso 1', 1, 'Cubículo silencioso con escritorio y lámpara. Ideal para concentración.', '08:00', '21:00', 180, 'teal',
                null, ['enchufes', 'wifi']],
            ['Sala Grupal B2', 'Biblioteca Central, piso 2', 6, 'Mesa para seis personas, pizarra blanca y pantalla para conectar notebook.', '08:00', '21:00', 120, 'blue',
                $biblioteca, ['aire-acondicionado', 'pantalla', 'pizarra', 'enchufes', 'wifi', 'llave', 'control-aire', 'cable-hdmi', 'plumones']],
            ['Sala Grupal B3', 'Biblioteca Central, piso 2', 8, 'Sala amplia con pizarra doble. Buena para trabajos de curso.', '08:00', '21:00', 120, 'violet',
                $biblioteca, ['calefaccion', 'pizarra', 'enchufes', 'wifi', 'llave', 'plumones']],
            ['Sala de Proyectos C1', 'Edificio de Ingeniería, piso 3', 12, 'Proyector, pizarra y mesas modulares para presentaciones y ensayos.', '09:00', '20:00', 180, 'amber',
                'Conserjería, Edificio de Ingeniería piso 1', ['aire-acondicionado', 'proyector', 'pizarra', 'enchufes', 'wifi', 'llave', 'control-aire', 'control-proyector', 'cable-hdmi', 'plumones']],
            ['Laboratorio de Estudio D4', 'Campus Norte, piso 1', 4, 'Cuatro puestos con computador y doble monitor.', '08:30', '19:00', 90, 'rose',
                'Recepción, Campus Norte', ['aire-acondicionado', 'computadores', 'enchufes', 'wifi', 'llave', 'control-aire']],
            ['Sala Silenciosa E2', 'Edificio de Humanidades, piso 2', 10, 'Espacio compartido de lectura en silencio absoluto.', '08:00', '22:00', 240, 'lime',
                null, ['calefaccion', 'enchufes', 'wifi']],
        ];

        $amenities = Amenity::pluck('id', 'slug');

        foreach ($rooms as [$name, $location, $capacity, $description, $opens, $closes, $max, $color, $pickup, $items]) {
            $room = Room::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'location' => $location,
                'pickup_point' => $pickup,
                'capacity' => $capacity,
                'description' => $description,
                'opens_at' => $opens,
                'closes_at' => $closes,
                'max_duration_minutes' => $max,
                'is_active' => true,
                'color' => $color,
            ]);

            $room->amenities()->sync($amenities->only($items)->values());
        }

        // Una sala desactivada para mostrar el estado en el panel de administración.
        Room::create([
            'name' => 'Sala Multimedia F1',
            'slug' => 'sala-multimedia-f1',
            'location' => 'Biblioteca Central, piso 3',
            'pickup_point' => $biblioteca,
            'capacity' => 6,
            'description' => 'En mantención: renovación de equipos audiovisuales.',
            'opens_at' => '08:00',
            'closes_at' => '21:00',
            'max_duration_minutes' => 120,
            'is_active' => false,
            'color' => 'slate',
        ])->amenities()->sync($amenities->only(['proyector', 'pantalla', 'llave', 'control-proyector'])->values());
    }
}
