<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Group;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/admin/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $totalUsers = User::count();
        $totalClients = User::where('role', 'CLIENT')->count();
        $totalProviders = User::where('role', 'PROVIDER')->count();
        $totalServices = Course::count();
        $approvedServices = Course::where('status', 'APPROVED')->count();
        $pendingServices = Course::where('status', 'PENDING_APPROVAL')->count();
        $totalGroups = Group::count();
        $activeGroups = Group::whereIn('status', ['OPEN', 'RESERVED'])->count();
        $fullGroups = Group::where('status', 'FULL')->count();

        $totalReservations = Reservation::count();
        $paidReservations = Reservation::where('status', 'PAID')->count();
        $pendingReservations = Reservation::where('status', 'PENDING')->count();

        // Ingresos totales
        $totalRevenue = Reservation::where('status', 'PAID')->sum('frozen_price');

        // Ingresos del mes actual
        $monthlyRevenue = Reservation::where('status', 'PAID')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('frozen_price');

        // Tasa de conversión
        $conversionRate = $totalReservations > 0
            ? round(($paidReservations / $totalReservations) * 100, 2)
            : 0;

        return response()->json([
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'clients' => $totalClients,
                    'providers' => $totalProviders,
                ],
                'services' => [
                    'total' => $totalServices,
                    'approved' => $approvedServices,
                    'pending' => $pendingServices,
                ],
                'groups' => [
                    'total' => $totalGroups,
                    'active' => $activeGroups,
                    'full' => $fullGroups,
                ],
                'reservations' => [
                    'total' => $totalReservations,
                    'paid' => $paidReservations,
                    'pending' => $pendingReservations,
                    'conversion_rate' => $conversionRate,
                ],
                'revenue' => [
                    'total' => round($totalRevenue, 2),
                    'this_month' => round($monthlyRevenue, 2),
                ],
            ],
            'message' => 'Dashboard de administración.',
            'status' => 'success',
        ]);
    }

    /**
     * GET /api/v1/admin/audit-log
     */
    public function auditLog(Request $request): JsonResponse
    {
        $logs = AuditLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'data' => $logs,
            'message' => 'Historial de acciones administrativas.',
            'status' => 'success',
        ]);
    }
}
