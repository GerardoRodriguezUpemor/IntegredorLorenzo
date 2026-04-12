<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Group;
use App\Models\Reservation;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    /**
     * GET /api/v1/teacher/courses/{courseId}/subscribers
     */
    public function index(Request $request, string $courseId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $teacher = Teacher::where('user_id', (string) $user->_id)->first();

        $course = Course::where('_id', $courseId)
            ->where('teacher_id', (string) $teacher->_id)
            ->with('groups')
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Curso no encontrado.', 'status' => 'error'], 404);
        }

        $groupIds = $course->groups->pluck('_id')->map(fn($id) => (string) $id);

        $reservations = Reservation::whereIn('group_id', $groupIds)
            ->whereIn('status', ['PENDING', 'PAID'])
            ->with(['user', 'group'])
            ->get();

        $subscribers = $reservations->map(fn($r) => [
            'student_id' => $r->user_id,
            'student_name' => $r->user->name ?? 'N/A',
            'student_email' => $r->user->email ?? 'N/A',
            'group_name' => $r->group->name ?? 'N/A',
            'group_id' => $r->group_id,
            'status' => $r->status,
            'frozen_price' => $r->frozen_price,
            'reserved_at' => $r->created_at,
        ]);

        return response()->json([
            'data' => [
                'course' => [
                    'id' => (string) $course->_id,
                    'name' => $course->name,
                ],
                'total_subscribers' => $subscribers->count(),
                'subscribers' => $subscribers,
                'groups_summary' => $course->groups->map(fn($g) => [
                    'id' => (string) $g->_id,
                    'name' => $g->name,
                    'current_count' => $g->current_count,
                    'max_capacity' => $g->max_capacity,
                    'status' => $g->status,
                ]),
            ],
            'message' => 'Suscriptores del curso.',
            'status' => 'success',
        ]);
    }
}
