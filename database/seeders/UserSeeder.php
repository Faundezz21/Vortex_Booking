<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Cuentas de demostración (contraseña: "password").
     *  - admin@vortexbooking.test   → rol administrador
     *  - usuario@vortexbooking.test → rol usuario
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Administrador Demo',
            'email' => 'admin@vortexbooking.test',
        ]);

        User::factory()->create([
            'name' => 'Usuario Demo',
            'email' => 'usuario@vortexbooking.test',
        ]);

        User::factory(60)->create();
    }
}
