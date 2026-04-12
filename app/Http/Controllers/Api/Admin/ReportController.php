<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * GET /api/v1/admin/reports/revenue
     */
    public function revenue(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $reservations = Reservation::where('status', 'PAID')
            ->where('paid_at', '>=', $from)
            ->where('paid_at', '<=', $to . ' 23:59:59')
            ->get();

        $totalRevenue = $reservations->sum('frozen_price');
        $totalTransactions = $reservations->count();
        $averageTicket = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        return response()->json([
            'data' => [
                'period' => ['from' => $from, 'to' => $to],
                'total_revenue' => round($totalRevenue, 2),
                'total_transactions' => $totalTransactions,
                'average_ticket' => round($averageTicket, 2),
            ],
            'message' => 'Reporte de ingresos.',
            'status' => 'success',
        ]);
    }

    /**
     * GET /api/v1/admin/reports/popular-courses
     */
    public function popularCourses(Request $request): JsonResponse
    {
        $courses = Course::approved()->with(['teacher', 'groups'])->get();

        $ranked = $courses->map(function ($course) {
            $totalStudents = $course->groups->sum('current_count');
            $totalGroups = $course->groups->count();
            $revenue = Reservation::where('status', 'PAID')
                ->whereIn('group_id', $course->groups->pluck('_id')->map(fn($id) => (string) $id))
                ->sum('frozen_price');

            return [
                'course_id' => (string) $course->_id,
                'course_name' => $course->name,
                'teacher_name' => $course->teacher->name ?? 'N/A',
                'total_students' => $totalStudents,
                'total_groups' => $totalGroups,
                'revenue' => round($revenue, 2),
            ];
        })->sortByDesc('total_students')->values();

        return response()->json([
            'data' => $ranked,
            'message' => 'Cursos más populares.',
            'status' => 'success',
        ]);
    }
}
