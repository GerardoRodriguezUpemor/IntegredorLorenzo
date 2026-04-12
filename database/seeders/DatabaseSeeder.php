<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Group;
use App\Models\ScheduleOption;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar colecciones
        User::truncate();
        Teacher::truncate();
        Course::truncate();
        Group::truncate();

        echo "🌱 Sembrando datos EP4 MicroCohorts...\n\n";

        // ═══════════════════════════════════════════════
        // 🛡️ ADMIN
        // ═══════════════════════════════════════════════
        $admin = User::create([
            'name' => 'Admin EP4',
            'email' => 'admin@ep4.edu',
            'password' => 'admin123',
            'role' => 'ADMIN',
        ]);
        echo "✅ Admin: admin@ep4.edu (pass: admin123)\n";

        // ═══════════════════════════════════════════════
        // 👨‍🏫 TEACHERS (Proveedores)
        // ═══════════════════════════════════════════════
        $teachersData = [
            ['name' => 'Profesor Vertiz', 'email' => 'vertiz@ep4.edu', 'specialty' => 'Ingeniería de Software'],
            ['name' => 'Profesora Sandra', 'email' => 'sandra@ep4.edu', 'specialty' => 'Matemáticas'],
            ['name' => 'Profesora Antonia', 'email' => 'antonia@ep4.edu', 'specialty' => 'Ciencias de la Computación'],
        ];

        $teachers = [];
        foreach ($teachersData as $td) {
            $user = User::create([
                'name' => $td['name'],
                'email' => $td['email'],
                'password' => 'teacher123',
                'role' => 'TEACHER',
            ]);

            $teacher = Teacher::create([
                'user_id' => (string) $user->_id,
                'name' => $td['name'],
                'email' => $td['email'],
                'specialty' => $td['specialty'],
            ]);

            $teachers[$td['name']] = $teacher;
            echo "✅ Teacher: {$td['email']} (pass: teacher123) — {$td['specialty']}\n";
        }

        // ═══════════════════════════════════════════════
        // 📚 COURSES (Todos APPROVED para testing)
        // ═══════════════════════════════════════════════
        $coursesData = [
            ['name' => 'Pruebas de Software', 'description' => 'Curso completo de testing: unitario, integración, E2E, TDD y BDD. Aprende a escribir software confiable.', 'teacher' => 'Profesor Vertiz'],
            ['name' => 'Ecuaciones Diferenciales', 'description' => 'Ecuaciones diferenciales ordinarias y parciales. Métodos analíticos y numéricos con aplicaciones en ingeniería.', 'teacher' => 'Profesora Sandra'],
            ['name' => 'Arquitectura de Sistemas', 'description' => 'Patrones de diseño, microservicios, DDD, CQRS y Event Sourcing. Diseña sistemas escalables y mantenibles.', 'teacher' => 'Profesora Antonia'],
            ['name' => 'Base de Datos', 'description' => 'SQL, NoSQL, diseño de esquemas, optimización de queries, indexación y transacciones distribuidas.', 'teacher' => 'Profesor Vertiz'],
        ];

        $courses = [];
        foreach ($coursesData as $cd) {
            $teacher = $teachers[$cd['teacher']];
            $course = Course::create([
                'name' => $cd['name'],
                'description' => $cd['description'],
                'teacher_id' => (string) $teacher->_id,
                'status' => 'APPROVED',
                'approved_by' => (string) $admin->_id,
                'approved_at' => now(),
            ]);
            $courses[] = $course;

            // 2 grupos por curso
            for ($g = 1; $g <= 2; $g++) {
                Group::create([
                    'course_id' => (string) $course->_id,
                    'name' => "Grupo {$g}",
                    'status' => 'OPEN',
                    'max_capacity' => 5,
                    'current_count' => 0,
                ]);
            }

            echo "✅ Curso: {$cd['name']} ({$cd['teacher']}) — 2 grupos\n";
        }

        // ═══════════════════════════════════════════════
        // 🎓 STUDENTS (Clientes)
        // ═══════════════════════════════════════════════
        $studentsData = [
            ['name' => 'Carlos García', 'email' => 'carlos@estudiante.edu'],
            ['name' => 'María López', 'email' => 'maria@estudiante.edu'],
            ['name' => 'Juan Hernández', 'email' => 'juan@estudiante.edu'],
            ['name' => 'Ana Martínez', 'email' => 'ana@estudiante.edu'],
            ['name' => 'Pedro Sánchez', 'email' => 'pedro@estudiante.edu'],
        ];

        foreach ($studentsData as $sd) {
            User::create([
                'name' => $sd['name'],
                'email' => $sd['email'],
                'password' => 'student123',
                'role' => 'STUDENT',
            ]);
            echo "✅ Student: {$sd['email']} (pass: student123)\n";
        }

        // ═══════════════════════════════════════════════
        // 📅 SCHEDULE OPTIONS (Demo: 3 fechas para el primer grupo)
        // ═══════════════════════════════════════════════
        $firstGroup = Group::first();
        if ($firstGroup) {
            $dates = [
                now()->addDays(7)->setHour(10)->setMinute(0),
                now()->addDays(10)->setHour(14)->setMinute(0),
                now()->addDays(14)->setHour(16)->setMinute(0),
            ];

            foreach ($dates as $date) {
                ScheduleOption::create([
                    'group_id' => (string) $firstGroup->_id,
                    'proposed_date' => $date,
                    'vote_count' => 0,
                ]);
            }
            echo "✅ 3 fechas propuestas para {$firstGroup->name} del primer curso\n";
        }

        echo "\n🎉 ¡Seed completado! Base de datos lista.\n";
        echo "───────────────────────────────────────\n";
        echo "Total: 1 admin + 3 teachers + 4 cursos + 8 grupos + 5 students\n";
    }
}
