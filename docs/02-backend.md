# 02 — Backend: Laravel 13 + MongoDB

## Introducción

El backend de MicroCohorts es una **API REST** construida sobre Laravel 13 y MongoDB. Sigue principios de **Domain-Driven Design (DDD)** dividiendo la lógica de negocio pura en una capa `Domain/` separada de la infraestructura y los controladores HTTP.

---

## Configuración del Entorno

El archivo `.env` contiene las variables críticas de conexión:

```dotenv
APP_NAME=MicroCohorts
APP_URL=http://127.0.0.1:8000

# MongoDB
MONGODB_URI=mongodb://localhost:27017
DB_DATABASE=microcohorts
DB_CONNECTION=mongodb

# Queue (para jobs de expiración de reservas)
QUEUE_CONNECTION=database
```

**Comando para iniciar el servidor:**
```bash
php artisan serve
# Escucha en: http://127.0.0.1:8000
```

**Comando para correr las migraciones y seeders:**
```bash
php artisan migrate:fresh --seed
```

---

## Arquitectura en Capas

### 1. Capa de Rutas (`routes/api.php`)

Todas las rutas están prefijadas con `/api/v1/`. Se dividen en:
- **Rutas Públicas**: `POST /auth/register`, `POST /auth/login`, `GET /courses`
- **Rutas Protegidas**: Requieren el header `X-User-Id` (middleware `SimpleAuth`)
  - Sub-grupo `ADMIN`: Middleware `RoleMiddleware:ADMIN`
  - Sub-grupo `TEACHER`: Middleware `RoleMiddleware:TEACHER`
  - Sub-grupo `STUDENT`: Middleware `RoleMiddleware:STUDENT`

### 2. Capa de Middleware

#### `SimpleAuth.php`
Este middleware intercepta **todas las rutas protegidas**. Su lógica:
1. Extrae el header `X-User-Id` del request HTTP.
2. Busca el usuario en MongoDB con `User::find($userId)`.
3. Si no existe, devuelve `401 Unauthorized`.
4. Si existe, **inyecta** el objeto `User` en los atributos del request para que los controladores lo usen: `$request->attributes->set('authenticated_user', $user)`.

> ⚠️ **Nota de Producción:** En un entorno real se reemplazaría con **Laravel Sanctum** o **JWT tokens** para mayor seguridad.

#### `RoleMiddleware.php`
Se aplica después de `SimpleAuth`. Verifica que el campo `role` del usuario autenticado coincida con el rol requerido por la ruta (ej. `ADMIN`, `TEACHER`, `STUDENT`). Si el rol no coincide, devuelve `403 Forbidden`.

### 3. Capa de Controladores (`app/Http/Controllers/Api/`)

Organizados por rol:

```
Controllers/Api/
├── AuthController.php          ← Login y Registro
├── Admin/
│   ├── CourseApprovalController.php  ← Aprobar/Rechazar cursos
│   ├── DashboardController.php       ← Dashboard y AuditLog
│   ├── UserManagementController.php  ← Gestión de usuarios
│   └── ReportController.php          ← Reportes de ingresos
├── Teacher/
│   ├── CourseManagementController.php ← CRUD de cursos
│   ├── SubscriberController.php       ← Lista de alumnos inscritos
│   └── ScheduleController.php         ← Proponer fechas y ver votos
└── Student/
    ├── BookingController.php   ← Reservas (reserve/confirm)
    ├── CatalogController.php   ← Catálogo público de cursos
    ├── MyCoursesController.php ← Mis inscripciones
    ├── VoteController.php      ← Votar horarios
    └── ReceiptController.php   ← Descargar recibos
```

### 4. Capa de Dominio (`app/Domain/`)

Aquí reside la **lógica de negocio pura**, sin dependencias de Laravel. Esto facilita las pruebas unitarias.

#### `Domain/Pricing/PricingEngine.php`

Implementa el algoritmo de **Decaimiento Exponencial de Precios**. La fórmula toma la posición del próximo comprador (1°, 2°, 3°…) y calcula cuánto pagará:

```
Precio = PrecioBase × e^(-λ × (posicion - 1))
```

- Si eres el **1er alumno**: pagas el precio máximo.
- Si eres el **5to alumno**: pagas significativamente menos.

Esto crea un incentivo para que el Profesor consiga alumnos rápido, y para que los estudiantes "esperen" el último lugar más barato (Race Condition intencional).

#### `Domain/Scheduling/VotingEngine.php`

Maneja la lógica de votación de horarios:
- `getOptionsWithVotes($groupId)`: Retorna las opciones de fecha con su conteo de votos.
- `getWinningDate($groupId)`: Determina la fecha con más votos. En caso de empate, elige la más cercana.

#### `Domain/Booking/`

Contiene:
- `GroupRepositoryInterface.php`: Contrato abstracto para el repositorio de grupos.
- `Exceptions/GroupFullException.php`: Excepción lanzada cuando se intenta reservar en un grupo lleno.

### 5. Capa de Infraestructura (`app/Infrastructure/`)

#### `MongoGroupRepository.php`

Implementación concreta del `GroupRepositoryInterface` usando MongoDB. El método crítico es `reserveSeat()`:

```php
public function reserveSeat(Group $group, User $user, ScheduleOption $option): Reservation
{
    // 1. Verificar que el grupo no está lleno
    if ($group->isFull()) {
        throw new GroupFullException("El grupo {$group->name} está lleno.");
    }
    
    // 2. Calcular el precio para esta posición
    $position = $group->current_count + 1;
    $priceBreakdown = $this->pricingEngine->calculate($position);
    
    // 3. Crear la reserva en estado PENDING con TTL de 5 minutos
    $reservation = Reservation::create([
        'user_id'        => (string) $user->_id,
        'group_id'       => (string) $group->_id,
        'frozen_price'   => $priceBreakdown->finalPrice,
        'price_breakdown'=> $priceBreakdown->toArray(),
        'status'         => ReservationStatus::PENDING->value,
        'expires_at'     => now()->addMinutes(5),
        'schedule_option_id' => (string) $option->_id,
    ]);
    
    // 4. Incrementar contador del grupo
    $group->increment('current_count');
    
    return $reservation;
}
```

### 6. Modelos MongoDB (`app/Models/`)

Los modelos heredan de `MongoDB\Laravel\Eloquent\Model` en lugar del `Model` estándar de Laravel. Los IDs son `ObjectId` de MongoDB.

| Modelo | Colección | Descripción |
|---|---|---|
| `User` | `users` | Usuario con `role` (STUDENT/TEACHER/ADMIN) |
| `Teacher` | `teachers` | Perfil extendido del profesor, relacionado a `User` |
| `Course` | `courses` | Curso con `status` (DRAFT/PENDING_APPROVAL/APPROVED/REJECTED) |
| `Group` | `groups` | Grupo/cohorte de un curso. Tiene `current_count` y `max_capacity=5` |
| `Reservation` | `reservations` | Reserva de un estudiante. Tiene `frozen_price` y `expires_at` |
| `ScheduleOption` | `schedule_options` | Fecha propuesta por el profesor para un grupo |
| `ScheduleVote` | `schedule_votes` | Voto de un estudiante por una `ScheduleOption` |
| `AuditLog` | `audit_logs` | Registro de acciones administrativas |

---

## Jobs Asíncronos (`app/Jobs/`)

### `ReleaseReservationJob.php`

Se despacha automáticamente cuando un estudiante **reserva** un asiento. Se ejecuta con un delay de **5 minutos**:

```php
ReleaseReservationJob::dispatch((string) $reservation->_id)
    ->delay(now()->addMinutes(5));
```

Si al ejecutarse, la reserva sigue en estado `PENDING` (el estudiante nunca pagó), el job:
1. Cambia el status a `EXPIRED`.
2. Decrementa el `current_count` del grupo, liberando el asiento.

---

## Sistema de Autenticación

### Flujo de Login

```
POST /api/v1/auth/login
Body: { email: "student@ep4.edu", password: "student123" }

↓ AuthController::login()
↓ Busca User por email
↓ Verifica password con bcrypt
↓ Retorna: { user: { id, name, email, role }, token: null }
```

> El token es actualmente `null` porque se usa `SimpleAuth` (basado en `X-User-Id` header). Al migrar a Sanctum, aquí se retornaría el Bearer Token.

---

## Ciclo de Vida de un Curso: Estados

```
DRAFT ──────────────────────► PENDING_APPROVAL
(creado por Teacher)          (Teacher pide revisión)
                                      │
                    ┌─────────────────┴──────────────────┐
                    ▼                                      ▼
                APPROVED                             REJECTED
         (Admin lo aprueba)                    (Admin lo rechaza)
                    │
                    ▼
         Aparece en el Catálogo
         Público de Estudiantes
```

---

## Pruebas

La suite de pruebas usa **PHPUnit** con mocks para MongoDB:

```bash
php artisan test
# o
./vendor/bin/phpunit
```

Los test unitarios prueban `PricingEngine` y `VotingEngine` de forma aislada.  
Los test de feature simulan el flujo completo de la API usando mocks de repositorios para no depender de una conexión real a MongoDB.
