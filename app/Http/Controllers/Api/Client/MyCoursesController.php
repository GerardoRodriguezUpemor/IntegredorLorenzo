<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyCoursesController extends Controller
{
    /**
     * GET /api/v1/my-courses
     * Mis cursos inscritos/pagados.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');

        $reservations = Reservation::where('user_id', (string) $user->_id)
            ->whereIn('status', ['PENDING', 'PAID'])
            ->with(['group.course.teacher', 'group.scheduleOptions'])
            ->orderBy('created_at', 'desc')
            ->get();

        $courses = $reservations->map(function ($reservation) {
            $group = $reservation->group;
            $course = $group?->course;
            $teacher = $course?->teacher;

            return [
                'reservation_id' => (string) $reservation->_id,
                'status' => $reservation->status,
                'frozen_price' => $reservation->frozen_price,
                'paid_at' => $reservation->paid_at,
                'expires_at' => $reservation->status === 'PENDING' ? $reservation->expires_at : null,
                'course' => [
                    'id' => $course ? (string) $course->_id : null,
                    'name' => $course->name ?? 'N/A',
                    'description' => $course->description ?? '',
                ],
                'teacher' => [
                    'name' => $teacher->name ?? 'N/A',
                    'specialty' => $teacher->specialty ?? 'N/A',
                ],
                'group' => [
                    'id' => (string) $group->_id,
                    'name' => $group->name ?? 'N/A',
                    'current_count' => $group->current_count,
                    'max_capacity' => $group->max_capacity,
                ],
                'schedule_options' => $group->scheduleOptions->map(fn($opt) => [
                    'id' => (string) $opt->_id,
                    'proposed_date' => $opt->proposed_date,
                    'vote_count' => $opt->vote_count,
                ]),
            ];
        });

        return response()->json([
            'data' => $courses,
            'message' => 'Mis cursos inscritos.',
            'status' => 'success',
        ]);
    }
}
