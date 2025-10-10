<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la columna booleana is_admin a la tabla users.
     * - Por defecto: false (0) → todos los usuarios son “normales” salvo que se marque explícitamente.
     * - Se coloca después de password para mantener un orden lógico.
     * - Se usan guardas (hasColumn) para evitar errores si ya existe la columna.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')
                    ->default(false)       // por defecto no es admin
                    ->after('password');   // orden visual en el esquema
            });
        }
    }

    /**
     * Revierte el cambio eliminando is_admin (si existe).
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }
};
