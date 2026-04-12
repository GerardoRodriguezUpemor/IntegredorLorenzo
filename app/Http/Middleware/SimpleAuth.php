<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware simple de autenticación.
 * Espera un header X-User-Id con el ID del usuario.
 * (Para producción se reemplazaría con Sanctum/JWT)
 */
class SimpleAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->header('X-User-Id');

        if (!$userId) {
            return response()->json([
                'message' => 'Header X-User-Id requerido.',
                'status' => 'error',
            ], 401);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
                'status' => 'error',
            ], 401);
        }

        // Inyectar usuario en el request para uso posterior
        $request->attributes->set('authenticated_user', $user);

        return $next($request);
    }
}
