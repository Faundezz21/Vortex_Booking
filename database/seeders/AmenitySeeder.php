<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

// puebla el catalogo inicial de comodidades y equipamiento
class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (Amenity::CATALOG as $i => [$slug, $name, $icon, $kind]) {
            Amenity::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'icon' => $icon,
                'kind' => $kind,
                'sort' => $i,
            ]);
        }
    }
}
