<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\CourseApprovalController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Provider\CourseManagementController;
use App\Http\Controllers\Api\Provider\SubscriberController;
use App\Http\Controllers\Api\Provider\ScheduleController;
use App\Http\Controllers\Api\Provider\GroupReportController;
use App\Http\Controllers\Api\Client\CatalogController;
use App\Http\Controllers\Api\Client\BookingController;
use App\Http\Controllers\Api\Client\VoteController;
use App\Http\Controllers\Api\Client\MyCoursesController;
use App\Http\Controllers\Api\Client\ReceiptController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SimpleAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| EP4 MicroCohorts — API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ═══════════════════════════════════════════════
    // 🔓 AUTH (Público)
    // ═══════════════════════════════════════════════
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // ═══════════════════════════════════════════════
    // 📚 CATÁLOGO PÚBLICO (no requiere auth)
    // ═══════════════════════════════════════════════
    Route::get('/courses', [CatalogController::class, 'index']);
    Route::get('/courses/{courseId}', [CatalogController::class, 'show']);

    // ═══════════════════════════════════════════════
    // 🔒 RUTAS PROTEGIDAS (requiere X-User-Id header)
    // ═══════════════════════════════════════════════
    Route::middleware(SimpleAuth::class)->group(function () {
        // 👤 PERFIL UNIFICADO (Compartido por todos los roles)
        Route::get('/profile', [\App\Http\Controllers\Api\Shared\ProfileController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\Shared\ProfileController::class, 'update']);
        Route::get('/exchange-rate', function(\App\Domain\Shared\Services\ExchangeRateService $service) {
            return response()->json(['rate' => $service->getMxnToUsdRate()]);
        });

        // ─────────────────────────────────────────
        // 🛡️ ADMIN
        // ─────────────────────────────────────────
        Route::prefix('admin')->middleware(RoleMiddleware::class . ':ADMIN')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/courses/pending', [CourseApprovalController::class, 'pending']);
            Route::get('/courses', [CourseApprovalController::class, 'index']);
            Route::get('/courses/report', [CourseApprovalController::class, 'downloadReport']);
            Route::patch('/courses/{courseId}/approve', [CourseApprovalController::class, 'approve']);
            Route::patch('/courses/{courseId}/reject', [CourseApprovalController::class, 'reject']);
            Route::get('/users', [UserManagementController::class, 'index']);
            Route::get('/users/report', [UserManagementController::class, 'downloadReport']);
            Route::patch('/users/{userId}/toggle-status', [UserManagementController::class, 'toggleStatus']);
            Route::delete('/users/{userId}', [UserManagementController::class, 'destroy']);
            Route::get('/reports/revenue', [ReportController::class, 'revenue']);
            Route::get('/reports/popular-courses', [ReportController::class, 'popularCourses']);
            Route::get('/audit-log', [DashboardController::class, 'auditLog']);
        });

        // ─────────────────────────────────────────
        // 👨‍💼 PROVIDER (Proveedor)
        // ─────────────────────────────────────────
        Route::prefix('provider')->middleware(RoleMiddleware::class . ':PROVIDER')->group(function () {
            Route::get('/courses', [CourseManagementController::class, 'index']);
            Route::post('/courses', [CourseManagementController::class, 'store']);
            Route::put('/courses/{courseId}', [CourseManagementController::class, 'update']);
            Route::delete('/courses/{courseId}', [CourseManagementController::class, 'destroy']);
            Route::patch('/courses/{courseId}/submit', [CourseManagementController::class, 'submit']);
            Route::get('/courses/{courseId}/subscribers', [SubscriberController::class, 'index']);
            Route::post('/groups/{groupId}/schedule', [ScheduleController::class, 'store']);
            Route::get('/groups/{groupId}/votes', [ScheduleController::class, 'votes']);
            Route::get('/groups/{groupId}/winning-date', [ScheduleController::class, 'winningDate']);
            Route::get('/groups/{groupId}/report', [GroupReportController::class, 'download']);
        });

        // ─────────────────────────────────────────
        // 🤝 CLIENT (Cliente)
        // ─────────────────────────────────────────
        Route::prefix('client')->middleware(RoleMiddleware::class . ':CLIENT')->group(function () {
            Route::get('/groups/{groupId}/pricing', [BookingController::class, 'pricing']);
            Route::post('/groups/{groupId}/reserve', [BookingController::class, 'reserve']);
            Route::patch('/reservations/{reservationId}/confirm', [BookingController::class, 'confirm']);
            Route::get('/groups/{groupId}/schedule', [VoteController::class, 'schedule']);
            Route::post('/groups/{groupId}/vote', [VoteController::class, 'vote']);
            Route::get('/my-courses', [MyCoursesController::class, 'index']);
            Route::get('/reservations/{reservationId}/receipt', [ReceiptController::class, 'download']);
        });
    });
});
