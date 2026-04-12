<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * GET /api/v1/courses
     * Ver catálogo de cursos aprobados (público).
     */
    public function index(Request $request): JsonResponse
    {
        $courses = Course::approved()
            ->with(['teacher', 'groups'])
            ->orderBy('approved_at', 'desc')
            ->get()
            ->map(function ($course) {
                $groups = $course->groups->map(fn($g) => [
                    'id' => (string) $g->_id,
                    'name' => $g->name,
                    'status' => $g->status,
                    'available_seats' => $g->availableSeats(),
                    'current_count' => $g->current_count,
                    'max_capacity' => $g->max_capacity,
                ]);

                return [
                    'id' => (string) $course->_id,
                    'name' => $course->name,
                    'description' => $course->description,
                    'teacher' => [
                        'name' => $course->teacher->name ?? 'N/A',
                        'specialty' => $course->teacher->specialty ?? 'N/A',
                    ],
                    'groups' => $groups,
                    'total_available_seats' => $groups->sum('available_seats'),
                ];
            });

        return response()->json([
            'data' => $courses,
            'message' => 'Catálogo de cursos disponibles.',
            'status' => 'success',
        ]);
    }

    /**
     * GET /api/v1/courses/{courseId}
     */
    public function show(Request $request, string $courseId): JsonResponse
    {
        $course = Course::approved()
            ->with(['teacher', 'groups'])
            ->find($courseId);

        if (!$course) {
            return response()->json(['message' => 'Curso no encontrado.', 'status' => 'error'], 404);
        }

        return response()->json([
            'data' => [
                'id' => (string) $course->_id,
                'name' => $course->name,
                'description' => $course->description,
                'teacher' => [
                    'name' => $course->teacher->name ?? 'N/A',
                    'specialty' => $course->teacher->specialty ?? 'N/A',
                ],
                'groups' => $course->groups->map(fn($g) => [
                    'id' => (string) $g->_id,
                    'name' => $g->name,
                    'status' => $g->status,
                    'available_seats' => $g->availableSeats(),
                    'current_count' => $g->current_count,
                    'max_capacity' => $g->max_capacity,
                ]),
            ],
            'message' => 'Detalle del curso.',
            'status' => 'success',
        ]);
    }
}
