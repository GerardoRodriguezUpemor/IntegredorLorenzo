<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseApprovalController extends Controller
{
    /**
     * GET /api/v1/admin/courses/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $courses = Course::pendingApproval()
            ->with('teacher')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $courses,
            'message' => 'Cursos pendientes de aprobación.',
            'status' => 'success',
        ]);
    }

    /**
     * GET /api/v1/admin/courses
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Course::with('teacher');

        if ($status) {
            $query->where('status', strtoupper($status));
        }

        $courses = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $courses,
            'message' => 'Lista de cursos.',
            'status' => 'success',
        ]);
    }

    /**
     * PATCH /api/v1/admin/courses/{courseId}/approve
     */
    public function approve(Request $request, string $courseId): JsonResponse
    {
        $course = Course::find($courseId);

        if (!$course) {
            return response()->json(['message' => 'Curso no encontrado.', 'status' => 'error'], 404);
        }

        if ($course->status !== 'PENDING_APPROVAL') {
            return response()->json([
                'message' => "El curso no está pendiente de aprobación (status actual: {$course->status}).",
                'status' => 'error',
            ], 409);
        }

        $admin = $request->attributes->get('authenticated_user');

        $course->update([
            'status' => 'APPROVED',
            'approved_by' => (string) $admin->_id,
            'approved_at' => now(),
        ]);

        AuditLog::record(
            (string) $admin->_id,
            'COURSE_APPROVED',
            'Course',
            $courseId,
            ['course_name' => $course->name]
        );

        return response()->json([
            'data' => $course->fresh()->load('teacher'),
            'message' => 'Curso aprobado exitosamente.',
            'status' => 'success',
        ]);
    }

    /**
     * PATCH /api/v1/admin/courses/{courseId}/reject
     */
    public function reject(Request $request, string $courseId): JsonResponse
    {
        $course = Course::find($courseId);

        if (!$course) {
            return response()->json(['message' => 'Curso no encontrado.', 'status' => 'error'], 404);
        }

        if ($course->status !== 'PENDING_APPROVAL') {
            return response()->json([
                'message' => "El curso no está pendiente de aprobación.",
                'status' => 'error',
            ], 409);
        }

        $admin = $request->attributes->get('authenticated_user');

        $course->update([
            'status' => 'REJECTED',
            'rejection_reason' => $request->input('reason', 'Sin razón especificada.'),
        ]);

        AuditLog::record(
            (string) $admin->_id,
            'COURSE_REJECTED',
            'Course',
            $courseId,
            ['course_name' => $course->name, 'reason' => $course->rejection_reason]
        );

        return response()->json([
            'data' => $course->fresh(),
            'message' => 'Curso rechazado.',
            'status' => 'success',
        ]);
    }
}
