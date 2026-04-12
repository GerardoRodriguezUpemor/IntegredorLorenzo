<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Group;
use App\Models\Reservation;
use App\Models\ScheduleOption;
use App\Models\ScheduleVote;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Prueba de Feature que simula el flujo completo de la aplicación
 * involucrando los 3 roles: Admin, Teacher, y Student.
 */
class PlatformFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Limpiamos las colecciones de base de datos antes de la prueba
        User::truncate();
        Teacher::truncate();
        Course::truncate();
        Group::truncate();
        Reservation::truncate();
        ScheduleOption::truncate();
        ScheduleVote::truncate();
        AuditLog::truncate();
    }

    public function test_full_platform_flow()
    {
        // Add Queue fake to prevent the ReleaseReservationJob from running automatically after 5 minutes and cancelling the reservation during test
        \Illuminate\Support\Facades\Queue::fake();
        // ------------------------------------------------------------------
        // PASO 1: Creación del Admin
        // ------------------------------------------------------------------
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => '123', 'role' => 'ADMIN']);

        // ------------------------------------------------------------------
        // PASO 2: Creación del Teacher y creación de su Curso
        // ------------------------------------------------------------------
        $teacherUser = User::create(['name' => 'Prof. X', 'email' => 'profx@test.com', 'password' => '123', 'role' => 'TEACHER']);
        $teacher = Teacher::create(['user_id' => (string) $teacherUser->_id, 'name' => 'Prof. X', 'email' => 'profx@test.com', 'specialty' => 'Mutantes']);

        // Teacher crea curso como DRAFT
        $response = $this->withHeader('X-User-Id', (string) $teacherUser->_id)
            ->postJson('/api/v1/teacher/courses', [
                'name' => 'Técnicas Mentales Avanzadas',
                'description' => 'Aprende a comunicarte mentalmente',
                'groups_count' => 1
            ]);
        $response->assertStatus(201);
        $courseId = $response->json('data._id') ?? $response->json('data.id');

        // Teacher envía curso a revisión
        $this->withHeader('X-User-Id', (string) $teacherUser->_id)
            ->patchJson("/api/v1/teacher/courses/{$courseId}/submit")
            ->assertStatus(200);

        // ------------------------------------------------------------------
        // PASO 3: Admin Aprueba el Curso
        // ------------------------------------------------------------------
        $this->withHeader('X-User-Id', (string) $admin->_id)
            ->patchJson("/api/v1/admin/courses/{$courseId}/approve")
            ->assertStatus(200);

        $course = Course::with('groups')->find($courseId);
        $this->assertEquals('APPROVED', $course->status);
        $groupId = (string) $course->groups->first()->_id;

        // ------------------------------------------------------------------
        // PASO 4: Teacher propone fechas para los grupos (Votación inicializada)
        // ------------------------------------------------------------------
        $date1 = now()->addDays(5)->format('Y-m-d H:i:s');
        $date2 = now()->addDays(7)->format('Y-m-d H:i:s');
        $date3 = now()->addDays(10)->format('Y-m-d H:i:s');

        $scheduleResponse = $this->withHeader('X-User-Id', (string) $teacherUser->_id)
            ->postJson("/api/v1/teacher/groups/{$groupId}/schedule", [
                'dates' => [$date1, $date2, $date3]
            ]);
        $scheduleResponse->assertStatus(201);

        $optionId = $scheduleResponse->json('data.0._id') ?? $scheduleResponse->json('data.0.id');

        // ------------------------------------------------------------------
        // PASO 5: Un Estudiante se inscribe (Se convierte en el 1ero) Coge lugar y vota
        // ------------------------------------------------------------------
        $studentUser = User::create(['name' => 'Wolverine', 'email' => 'logan@test.com', 'password' => '123', 'role' => 'STUDENT']);

        // El estudiante revisa el precio del grupo
        $pricingResponse = $this->withHeader('X-User-Id', (string) $studentUser->_id)
            ->getJson("/api/v1/groups/{$groupId}/pricing");
        
        $pricingResponse->assertStatus(200);
        $this->assertEquals(120.0, $pricingResponse->json('data.pricing.current_price')); // Por ser el primero n=1 -> 120

        // El estudiante realiza la reserva atómica Y PONE SU VOTO
        $reserveResponse = $this->withHeader('X-User-Id', (string) $studentUser->_id)
            ->postJson("/api/v1/groups/{$groupId}/reserve", [
                'schedule_option_id' => $optionId
            ]);
        
        if ($reserveResponse->status() !== 201) {
            $reserveResponse->dump();
        }
        $reserveResponse->assertStatus(201);
        $reservationId = $reserveResponse->json('data.reservation_id');

        // El estudiante paga
        $this->withHeader('X-User-Id', (string) $studentUser->_id)
            ->patchJson("/api/v1/reservations/{$reservationId}/confirm")
            ->assertStatus(200);

        // Validamos que el grupo ahora tiene 1 inscrito y su status pasó a RESERVED
        $group = Group::find($groupId);
        $this->assertEquals(1, $group->current_count);
        $this->assertEquals('RESERVED', $group->status);

        // ------------------------------------------------------------------
        // PASO 6: Teacher ve la fecha ganadora
        // ------------------------------------------------------------------
        $winningResponse = $this->withHeader('X-User-Id', (string) $teacherUser->_id)
            ->getJson("/api/v1/teacher/groups/{$groupId}/winning-date");
        $winningResponse->assertStatus(200);
        $this->assertEquals(1, $winningResponse->json('data.total_votes'));

        // ------------------------------------------------------------------
        // PASO 7: Admin verifica el dashboard de reportes/stats
        // ------------------------------------------------------------------
        $adminDashboard = $this->withHeader('X-User-Id', (string) $admin->_id)
            ->getJson("/api/v1/admin/dashboard");
        $adminDashboard->assertStatus(200);
        
        $this->assertEquals(120.0, $adminDashboard->json('data.revenue.total'));
        $this->assertEquals(1, $adminDashboard->json('data.users.students'));
    }
}
