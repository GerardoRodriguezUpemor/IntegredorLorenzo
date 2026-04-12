<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'role' => 'in:STUDENT,TEACHER',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
                'status' => 'error',
            ], 422);
        }

        // Verificar email único
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'El email ya está registrado.',
                'status' => 'error',
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role ?? 'STUDENT',
        ]);

        // Si es teacher, crear el perfil de teacher
        if ($user->role === 'TEACHER') {
            $user->teacher()->create([
                'name' => $user->name,
                'email' => $user->email,
                'specialty' => $request->specialty ?? 'General',
            ]);
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id' => (string) $user->_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
            'message' => 'Usuario registrado exitosamente.',
            'status' => 'success',
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
                'status' => 'error',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
                'status' => 'error',
            ], 401);
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id' => (string) $user->_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
            'message' => 'Login exitoso.',
            'status' => 'success',
        ]);
    }
}
