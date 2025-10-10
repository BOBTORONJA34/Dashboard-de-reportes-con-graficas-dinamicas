<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Bootstrap de la aplicación
|--------------------------------------------------------------------------
| Punto de entrada del kernel HTTP/CLI. Aquí registramos rutas y
| middlewares (en Laravel 11/12 ya no hay app/Http/Kernel.php).
|
| IMPORTANTE:
| - Declara routes/api.php dentro de ->withRouting(...) para que /api/* funcione.
| - Registra aliases de middlewares de RUTA dentro de ->withMiddleware(...).
*/
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Rutas web (Blade, vistas, controladores con middleware 'web')
        web: __DIR__.'/../routes/web.php',

        // ✅ Rutas API (imprescindible para que /api/... funcione)
        api: __DIR__.'/../routes/api.php',

        // Comandos de consola (artisan)
        commands: __DIR__.'/../routes/console.php',

        // Endpoint de salud (opcional)
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |--------------------------------------------------------------
        | Aliases de middleware de RUTA (equivalente a $routeMiddleware)
        |--------------------------------------------------------------
        | Aquí registramos nombres cortos que luego usamos en rutas:
        |   Route::middleware(['auth','admin'])->group(...)
        */
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class, // 👈 solo admins
        ]);

        /*
        |--------------------------------------------------------------
        | (Opcional) Registrar middlewares globales o por grupo
        |--------------------------------------------------------------
        | Ejemplos:
        | $middleware->appendToGroup('web', \App\Http\Middleware\Foo::class);
        | $middleware->prependToGroup('api', \App\Http\Middleware\Bar::class);
        */
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejo centralizado de excepciones (logs, renderizado, etc.)
        // Por ahora lo dejamos por defecto.
    })
    ->create();
