<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controladores de Auth de Breeze
|--------------------------------------------------------------------------
| Estos vienen con Breeze. Si faltan, revisa que hayas corrido:
|   composer require laravel/breeze --dev
|   php artisan breeze:install blade
|   php artisan migrate
*/
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;

/*
|--------------------------------------------------------------------------
| Controlador de Perfil (el que agrega las rutas profile.*)
|--------------------------------------------------------------------------
| Si no existe, créalo (te pasé antes un ProfileController básico).
*/
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Rutas para invitados (no autenticados)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Registro
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Recuperar contraseña
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // Reset de contraseña (link con token)
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Verificación de email (vista de “verifica tu email”)
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    // Endpoint que valida el link de verificación
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Reenviar email de verificación
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Confirmar contraseña (para acciones sensibles)
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Cambiar contraseña autenticado
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    // Cerrar sesión
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Rutas de PERFIL (solución a tu error)
    |--------------------------------------------------------------------------
    | Estas tres son las que necesita el layout para el link de “Perfil”:
    | - profile.edit     (formulario)
    | - profile.update   (PATCH)
    | - profile.destroy  (DELETE)
    */
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});
