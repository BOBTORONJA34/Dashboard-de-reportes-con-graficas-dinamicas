<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SaleController;

/*
|--------------------------------------------------------------------------
| Rutas WEB
|--------------------------------------------------------------------------
| Notas:
| - Breeze ya registra /login, /register, /password/* en routes/auth.php.
| - Protegemos todo lo “productivo” detrás de 'auth'.
| - La raíz "/" redirige al dashboard (si no hay sesión, el middleware
|   de /dashboard te manda a /login automáticamente).
*/

// Raíz -> redirige al dashboard (auth hará la magia de enviar a /login)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren sesión)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard (carga la vista + pasa categorias/regiones)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Exportación CSV (usada por el botón "Exportar CSV" del dashboard)
    Route::get('/reportes/export-csv', [DashboardController::class, 'exportCsv'])
        ->name('reportes.csv');

    // Importación CSV (sube ventas desde el dashboard)
    Route::post('/reportes/import-csv', [DashboardController::class, 'importCsv'])
        ->name('reportes.import');

    // Ventas (solo crear y guardar). La vista usa <x-app-layout> (Breeze).
    Route::resource('sales', SaleController::class)
        ->only(['create', 'store']);
});

/*
|--------------------------------------------------------------------------
| Rutas de autenticación Breeze
|--------------------------------------------------------------------------
| IMPORTANTÍSIMO: mantener esta línea; aquí viven login/register/profile/etc.
*/
require __DIR__.'/auth.php';
