<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Ver datos del perfil.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        
        if ($user->isTeacher()) {
            $user->load('teacher');
        }

        return response()->json([
            'data' => $user,
            'status' => 'success',
        ]);
    }

    /**
     * Actualizar perfil.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->_id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'specialty' => $user->isTeacher() ? 'required|string|max:255' : 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
                'status' => 'error',
            ], 422);
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Si es teacher, sincronizar con la colección de teachers
        if ($user->isTeacher()) {
            Teacher::where('user_id', (string) $user->_id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'specialty' => $request->specialty,
            ]);
        }

        return response()->json([
            'data' => $user->fresh($user->isTeacher() ? ['teacher'] : []),
            'message' => 'Perfil actualizado exitosamente.',
            'status' => 'success',
        ]);
    }
}
