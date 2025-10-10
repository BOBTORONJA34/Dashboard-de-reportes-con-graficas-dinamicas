<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea/actualiza un usuario administrador por defecto.
 * Toma credenciales de ENV si existen; si no, usa valores por defecto.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Credenciales por ENV (con fallback)
        $email = env('ADMIN_EMAIL', 'sidix34yolo@gmail.com');
        $name  = env('ADMIN_NAME', 'Administrador');
        $pass  = env('ADMIN_PASSWORD', 'administrador123'); // cámbialo en producción

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($pass),
                'is_admin' => true,
            ]
        );
    }
}
