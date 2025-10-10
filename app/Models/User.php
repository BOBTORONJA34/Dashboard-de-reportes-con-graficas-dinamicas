<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributos asignables masivamente.
     * NOTA: Por seguridad, NO incluimos 'is_admin' aquí.
     *       Así evitamos que un usuario se autoconceda admin vía forms comunes.
     *       El flag de admin lo setea explícitamente un admin (p.ej. en seeder o panel de admin).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atributos ocultos en serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de atributos.
     * - 'is_admin' como booleano para poder usar $user->is_admin directamente.
     * - 'password' => 'hashed' (Laravel se encarga de hashear al asignar).
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_admin'          => 'boolean', // 👈 MUY IMPORTANTE para el middleware/admin
    ];

    /**
     * Helper de conveniencia: $user->isAdmin()
     * (Opcional, pero cómodo para checks en Blade/controladores)
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
