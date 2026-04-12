<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\CourseApprovalController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Teacher\CourseManagementController;
use App\Http\Controllers\Api\Teacher\SubscriberController;
use App\Http\Controllers\Api\Teacher\ScheduleController;
use App\Http\Controllers\Api\Student\CatalogController;
use App\Http\Controllers\Api\Student\BookingController;
use App\Http\Controllers\Api\Student\VoteController;
use App\Http\Controllers\Api\Student\MyCoursesController;
use App\Http\Controllers\Api\Student\ReceiptController;
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

        // ─────────────────────────────────────────
        // 🛡️ ADMIN
        // ─────────────────────────────────────────
        Route::prefix('admin')->middleware(RoleMiddleware::class . ':ADMIN')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/courses/pending', [CourseApprovalController::class, 'pending']);
            Route::get('/courses', [CourseApprovalController::class, 'index']);
            Route::patch('/courses/{courseId}/approve', [CourseApprovalController::class, 'approve']);
            Route::patch('/courses/{courseId}/reject', [CourseApprovalController::class, 'reject']);
            Route::get('/users', [UserManagementController::class, 'index']);
            Route::patch('/users/{userId}/toggle-status', [UserManagementController::class, 'toggleStatus']);
            Route::get('/reports/revenue', [ReportController::class, 'revenue']);
            Route::get('/reports/popular-courses', [ReportController::class, 'popularCourses']);
            Route::get('/audit-log', [DashboardController::class, 'auditLog']);
        });

        // ─────────────────────────────────────────
        // 👨‍🏫 TEACHER (Proveedor)
        // ─────────────────────────────────────────
        Route::prefix('teacher')->middleware(RoleMiddleware::class . ':TEACHER')->group(function () {
            Route::get('/courses', [CourseManagementController::class, 'index']);
            Route::post('/courses', [CourseManagementController::class, 'store']);
            Route::put('/courses/{courseId}', [CourseManagementController::class, 'update']);
            Route::patch('/courses/{courseId}/submit', [CourseManagementController::class, 'submit']);
            Route::get('/courses/{courseId}/subscribers', [SubscriberController::class, 'index']);
            Route::post('/groups/{groupId}/schedule', [ScheduleController::class, 'store']);
            Route::get('/groups/{groupId}/votes', [ScheduleController::class, 'votes']);
            Route::get('/groups/{groupId}/winning-date', [ScheduleController::class, 'winningDate']);
        });

        // ─────────────────────────────────────────
        // 🎓 STUDENT (Cliente)
        // ─────────────────────────────────────────
        Route::middleware(RoleMiddleware::class . ':STUDENT')->group(function () {
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
