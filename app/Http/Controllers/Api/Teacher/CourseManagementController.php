<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Group;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseManagementController extends Controller
{
    /**
     * GET /api/v1/teacher/courses
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $teacher = Teacher::where('user_id', (string) $user->_id)->first();

        if (!$teacher) {
            return response()->json(['message' => 'Perfil de teacher no encontrado.', 'status' => 'error'], 404);
        }

        $courses = Course::where('teacher_id', (string) $teacher->_id)
            ->with('groups')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $courses,
            'message' => 'Mis cursos.',
            'status' => 'success',
        ]);
    }

    /**
     * POST /api/v1/teacher/courses
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
        $teacher = Teacher::where('user_id', (string) $user->_id)->first();

        if (!$teacher) {
            return response()->json(['message' => 'Perfil de teacher no encontrado.', 'status' => 'error'], 404);
        }

        $course = Course::create([
            'name' => $request->name,
            'description' => $request->description,
            'teacher_id' => (string) $teacher->_id,
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
            'message' => 'Curso creado como borrador. Usa /submit para enviarlo a revisión.',
            'status' => 'success',
        ], 201);
    }

    /**
     * PUT /api/v1/teacher/courses/{courseId}
     */
    public function update(Request $request, string $courseId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $teacher = Teacher::where('user_id', (string) $user->_id)->first();
        $course = Course::where('_id', $courseId)
            ->where('teacher_id', (string) $teacher->_id)
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Curso no encontrado.', 'status' => 'error'], 404);
        }

        if (!in_array($course->status, ['DRAFT', 'REJECTED'])) {
            return response()->json([
                'message' => 'Solo puedes editar cursos en borrador o rechazados.',
                'status' => 'error',
            ], 409);
        }

        $course->update($request->only(['name', 'description']));

        return response()->json([
            'data' => $course->fresh(),
            'message' => 'Curso actualizado.',
            'status' => 'success',
        ]);
    }

    /**
     * PATCH /api/v1/teacher/courses/{courseId}/submit
     */
    public function submit(Request $request, string $courseId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $teacher = Teacher::where('user_id', (string) $user->_id)->first();
        $course = Course::where('_id', $courseId)
            ->where('teacher_id', (string) $teacher->_id)
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Curso no encontrado.', 'status' => 'error'], 404);
        }

        if (!in_array($course->status, ['DRAFT', 'REJECTED'])) {
            return response()->json([
                'message' => 'Solo puedes enviar a revisión cursos en borrador o rechazados.',
                'status' => 'error',
            ], 409);
        }

        $course->update([
            'status' => 'PENDING_APPROVAL',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'data' => $course->fresh(),
            'message' => 'Curso enviado a revisión. Un administrador lo evaluará.',
            'status' => 'success',
        ]);
    }
}
