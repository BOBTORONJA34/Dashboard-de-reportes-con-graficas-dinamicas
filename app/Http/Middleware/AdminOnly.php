<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Permite pasar solo a usuarios autenticados con flag is_admin=true.
     * - Si no hay usuario autenticado → redirige a login.
     * - Si hay usuario pero no es admin → 403 (forbidden).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Si no hay sesión, redirige a login (mejor UX que un 403 directo).
        if (!$request->user()) {
            // Guarda la URL actual para volver después de login
            return redirect()->guest(route('login'));
        }

        // 2) Si hay sesión pero no es admin, prohíbe acceso.
        if (!$request->user()->is_admin) {
            abort(403, 'Solo administradores.');
        }

        // 3) Admin autenticado → continúa.
        return $next($request);
    }
}
