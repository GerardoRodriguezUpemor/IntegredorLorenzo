<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para proteger rutas por rol de usuario.
 * Uso: ->middleware('role:ADMIN') o ->middleware('role:ADMIN,TEACHER')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->attributes->get('authenticated_user');

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado.',
                'status' => 'error',
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este recurso.',
                'status' => 'error',
                'required_role' => $roles,
                'your_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
