<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Group;
use App\Models\Reservation;
use App\Models\ScheduleOption;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpieza total de la base de datos
        User::truncate();
        Provider::truncate();
        Course::truncate();
        Group::truncate();
        ScheduleOption::truncate();
        Reservation::truncate();
        AuditLog::truncate();

        echo "🚀 Preparando escenario de prueba: 4 clientes inscritos en 1 servicio de Vertiz...\n";

        // 2. Crear Administrador
        $admin = User::create([
            'name' => 'Admin EP4',
            'email' => 'admin@ep4.edu',
            'password' => 'admin123',
            'role' => 'ADMIN',
        ]);

        // 3. Crear Proveedor Vertiz
        $userVertiz = User::create([
            'name' => 'Proveedor Vertiz',
            'email' => 'vertiz@ep4.edu',
            'password' => 'teacher123',
            'role' => 'PROVIDER',
        ]);

        $providerVertiz = Provider::create([
            'user_id' => (string) $userVertiz->_id,
            'name' => 'Proveedor Vertiz',
            'email' => 'vertiz@ep4.edu',
            'specialty' => 'Ingeniería de Software',
        ]);

        // 4. Crear 1 Servicio Autorizado
        $course = Course::create([
            'name' => 'Pruebas de Software Avanzadas',
            'description' => 'Aprende TDD, BDD y arquitecturas limpias para crear software robusto.',
            'provider_id' => (string) $providerVertiz->_id,
            'status' => 'APPROVED',
            'approved_by' => (string) $admin->_id,
            'approved_at' => now(),
        ]);

        // 5. Crear 1 Grupo con capacidad 5
        $group = Group::create([
            'course_id' => (string) $course->_id,
            'name' => 'Grupo Único de Test',
            'status' => 'RESERVED', // Cambia de OPEN a RESERVED porque ya tendrá alumnos
            'max_capacity' => 5,
            'current_count' => 4, // Ya hay 4 inscritos
        ]);

        // 6. Proponer 3 fechas para votación
        $dates = [
            Carbon::now()->addDays(7)->setHour(10)->setMinute(0),
            Carbon::now()->addDays(10)->setHour(14)->setMinute(0),
            Carbon::now()->addDays(14)->setHour(16)->setMinute(0),
        ];

        $options = [];
        foreach ($dates as $date) {
            $options[] = ScheduleOption::create([
                'group_id' => (string) $group->_id,
                'proposed_date' => $date,
                'vote_count' => 1, // Simulamos 1 voto por opción para que haya dispersión
            ]);
        }

        // 7. Crear 4 Clientes y sus inscripciones (PAID)
        $clientsData = [
            ['name' => 'Carlos García', 'email' => 'carlos@cliente.edu'],
            ['name' => 'María López', 'email' => 'maria@cliente.edu'],
            ['name' => 'Juan Hernández', 'email' => 'juan@cliente.edu'],
            ['name' => 'Ana Martínez', 'email' => 'ana@cliente.edu'],
        ];

        foreach ($clientsData as $index => $cd) {
            $user = User::create([
                'name' => $cd['name'],
                'email' => $cd['email'],
                'password' => 'student123',
                'role' => 'CLIENT',
            ]);

            // Crear Reservación PAGADA
            Reservation::create([
                'user_id' => (string) $user->_id,
                'group_id' => (string) $group->_id,
                'schedule_option_id' => (string) $options[$index % 3]->_id,
                'status' => 'PAID',
                'frozen_price' => 50.00,
                'paid_at' => now(),
                'expires_at' => now()->addYears(1), // No expira
            ]);
        }

        // 8. Crear 1 Servicio PENDIENTE de Aprobación
        Course::create([
            'name' => 'Inteligencia Artificial con Python',
            'description' => 'Un curso práctico para crear redes neuronales y modelos de machine learning.',
            'provider_id' => (string) $providerVertiz->_id,
            'status' => 'PENDING_APPROVAL',
        ]);

        // Cliente extra (el que usará Gerardo para entrar)
        User::create([
            'name' => 'Gerardo Cliente',
            'email' => 'gerardo@cliente.edu',
            'password' => 'student123',
            'role' => 'CLIENT',
        ]);

        echo "\n✅ Escenario listo. \n";
        echo "Servicios: 1 (Vertiz) \n";
        echo "Grupo: {$group->name} (4/5 inscritos) \n";
        echo "Entra con: gerardo@cliente.edu (pass: student123) para ser el 5to.\n";
    }
}
