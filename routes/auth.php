<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controladores de Auth (Breeze)
|--------------------------------------------------------------------------
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
| Controlador de Perfil
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| RUTAS PARA INVITADOS (guest)
|--------------------------------------------------------------------------
| Aquí NO va el registro. El registro queda
| restringido a administradores autenticados.
*/
Route::middleware('guest')->group(function () {
    // Login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Recuperar contraseña
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    // Reset de contraseña (link con token)
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| REGISTRO SOLO PARA ADMIN (auth + admin)
|--------------------------------------------------------------------------
| Requiere dos cosas:
| 1) Middleware 'auth' (usuario logueado)
| 2) Middleware 'admin' (is_admin = true)
|
| Nota: El alias 'admin' debe estar registrado en bootstrap/app.php:
|   $middleware->alias(['admin' => \App\Http\Middleware\AdminOnly::class]);
*/
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Verificación de email (vista)
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');

    // Validar link de verificación
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Reenviar email de verificación
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Confirmación de contraseña (acciones sensibles)
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Cambiar contraseña autenticado
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Cerrar sesión
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | PERFIL DEL USUARIO (profile.*)
    |--------------------------------------------------------------------------
    | Necesarias para el link "Perfil" del layout.
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
