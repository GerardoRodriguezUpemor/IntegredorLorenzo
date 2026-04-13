<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Group;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseManagementController extends Controller
{
    /**
     * GET /api/v1/provider/courses
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();

        if (!$provider) {
            return response()->json(['message' => 'Perfil de proveedor no encontrado.', 'status' => 'error'], 404);
        }

        $courses = Course::where('provider_id', (string) $provider->_id)
            ->with('groups')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $courses,
            'message' => 'Mis servicios/cursos.',
            'status' => 'success',
        ]);
    }

    /**
     * POST /api/v1/provider/courses
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'groups_count' => 'integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
                'status' => 'error',
            ], 422);
        }

        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();

        if (!$provider) {
            return response()->json(['message' => 'Perfil de proveedor no encontrado.', 'status' => 'error'], 404);
        }

        $course = Course::create([
            'name' => $request->name,
            'description' => $request->description,
            'provider_id' => (string) $provider->_id,
            'status' => 'DRAFT',
        ]);

        // Crear grupos automáticamente
        $groupsCount = $request->input('groups_count', 2);
        for ($i = 1; $i <= $groupsCount; $i++) {
            Group::create([
                'course_id' => (string) $course->_id,
                'name' => "Grupo {$i}",
                'status' => 'OPEN',
                'max_capacity' => Group::MAX_CAPACITY,
                'current_count' => 0,
            ]);
        }

        return response()->json([
            'data' => $course->load('groups'),
            'message' => 'Servicio creado como borrador. Usa /submit para enviarlo a revisión.',
            'status' => 'success',
        ], 201);
    }

    /**
     * PUT /api/v1/provider/courses/{courseId}
     */
    public function update(Request $request, string $courseId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();
        $course = Course::where('_id', $courseId)
            ->where('teacher_id', (string) $provider->_id)
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Servicio no encontrado.', 'status' => 'error'], 404);
        }

        if (!in_array($course->status, ['DRAFT', 'REJECTED'])) {
            return response()->json([
                'message' => 'Solo puedes editar servicios en borrador o rechazados.',
                'status' => 'error',
            ], 409);
        }

        $course->update($request->only(['name', 'description']));

        return response()->json([
            'data' => $course->fresh(),
            'message' => 'Servicio actualizado.',
            'status' => 'success',
        ]);
    }

    /**
     * PATCH /api/v1/provider/courses/{courseId}/submit
     */
    public function submit(Request $request, string $courseId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();
        $course = Course::where('_id', $courseId)
            ->where('teacher_id', (string) $provider->_id)
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Servicio no encontrado.', 'status' => 'error'], 404);
        }

        if (!in_array($course->status, ['DRAFT', 'REJECTED'])) {
            return response()->json([
                'message' => 'Solo puedes enviar a revisión servicios en borrador o rechazados.',
                'status' => 'error',
            ], 409);
        }

        $course->update([
            'status' => 'PENDING_APPROVAL',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'data' => $course->fresh(),
            'message' => 'Servicio enviado a revisión. Un administrador lo evaluará.',
            'status' => 'success',
        ]);
    }

    /**
     * DELETE /api/v1/provider/courses/{courseId}
     */
    public function destroy(Request $request, string $courseId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();
        
        $course = Course::where('_id', $courseId)
            ->where('teacher_id', (string) $provider->_id)
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Servicio no encontrado.', 'status' => 'error'], 404);
        }

        // Solo permitir borrar si está en DRAFT o REJECTED por seguridad
        // (Si está APPROVED o PENDING, requiere acción administrativa o cerrar inscripciones)
        if (!in_array($course->status, ['DRAFT', 'REJECTED'])) {
            return response()->json([
                'message' => 'No puedes eliminar un registro que ya está publicado o en revisión.',
                'status' => 'error'
            ], 403);
        }

        $course->delete(); // Soft Delete

        return response()->json([
            'message' => 'Registro eliminado correctamente.',
            'status' => 'success',
        ]);
    }
}
