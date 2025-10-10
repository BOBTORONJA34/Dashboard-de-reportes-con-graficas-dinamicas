<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Bootstrap de la aplicación
|--------------------------------------------------------------------------
| Punto de entrada del kernel HTTP/CLI. Aquí registramos las rutas.
| OJO: En Laravel 11+, hay que declarar explícitamente el archivo
| routes/api.php dentro de ->withRouting(...). Si no, /api/* da 404.
*/
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Rutas web (blade, vistas, etc.)
        web: __DIR__.'/../routes/web.php',

        // ✅ Rutas API (imprescindible para que /api/... funcione)
        api: __DIR__.'/../routes/api.php',

        // Comandos de consola (artisan)
        commands: __DIR__.'/../routes/console.php',

        // Endpoint de salud (opcional)
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aquí puedes registrar middlewares globales si los necesitas.
        // Por ahora lo dejamos vacío.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejo centralizado de excepciones (logs, renderizado, etc.)
        // Por ahora lo dejamos por defecto.
    })
    ->create();
