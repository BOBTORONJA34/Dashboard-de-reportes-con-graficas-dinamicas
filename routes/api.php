<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StatsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estas rutas se sirven con el prefijo /api automáticamente.
| Ej: GET /api/filters/categories
*/

// --- Filtros ---
Route::get('/filters/categories', [StatsController::class, 'categories']);
Route::get('/filters/regions',    [StatsController::class, 'regions']);

// --- Datos estadísticos ---
Route::get('/stats/kpis',                 [StatsController::class, 'kpis']);
Route::get('/stats/sales-by-month',       [StatsController::class, 'salesByMonth']);
Route::get('/stats/sales-share-by-category', [StatsController::class, 'salesShareByCategory']);
