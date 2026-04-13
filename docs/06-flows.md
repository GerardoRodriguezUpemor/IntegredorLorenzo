# 06 — Flujos Completos por Rol

Este documento describe, paso a paso, cada interacción posible dentro de la plataforma para cada rol, indicando qué pantallas se visitan, qué API calls se realizan, y qué sucede en la base de datos.

---

## 🎓 FLUJO DEL ESTUDIANTE

### Registro e Inicio de Sesión

```
1. Estudiante visita http://localhost:5174/
   → Página Landing (pública)
   → Clic en "Comenzar" → navega a /register

2. Completa el formulario de registro:
   POST /api/v1/auth/register
   Body: { name, email, password, role: "STUDENT" }
   
   MongoDB crea: db.users { name, email, password_hash, role: "STUDENT" }
   
   → Frontend recibe el user y llama login() → guarda en localStorage
   → Redirige automáticamente a /student/courses

3. En sesiones futuras, al abrir el navegador:
   → AuthContext restaura user desde localStorage
   → Si el token existe, va directo al catálogo
```

---

### Explorar el Catálogo

```
4. Componente <Catalog /> se monta
   
   GET /api/v1/courses
   Header: X-User-Id: {studentId}
   
   Backend consulta MongoDB:
   db.courses.find({ status: "APPROVED" })
             .populate("groups")
             .populate("teacher")
   
   Frontend recibe array de cursos APROBADOS con sus grupos.
    
    ### Filtrado Reactivo (Frontend)
    - **Búsqueda**: `courses.filter(c => c.name.toLowerCase().includes(searchQuery))`
    - **Disponibilidad**: Oculta grupos donde `g.current_count === 5`.
    - **Fecha Próxima**: Si no hay votos, muestra la fecha más cercana; si hay votos, muestra la opción ganadora.
    
    Para cada grupo, aplica la fórmula de decaimiento exponencial
   localmente para mostrar el precio estimado actual:
   
   precio = 200 * Math.exp(-0.25 * current_count)
   
   → Render: cards de cursos con nombre, descripción,
     profesor, slots disponibles (N/5) y precio estimado
```

---

### Proceso de Reserva (Checkout)

```
5. Estudiante hace clic en "Reservar" en un curso
   → Navega a /student/checkout/{groupId}

6. <Checkout /> se monta y hace 2 llamadas en paralelo:

   a) GET /api/v1/groups/{groupId}/pricing
      → Backend calcula precio exacto para la posición (current_count+1)
      → Frontend muestra: "Precio para TI: $X.XX (posición N de 5)"
   
   b) GET /api/v1/groups/{groupId}/schedule
      → Backend trae las ScheduleOptions del grupo
      → Frontend muestra: 3 opciones de fecha con radio buttons

7. Estudiante elige su fecha preferida y hace clic en "Confirmar Reserva"
   
   POST /api/v1/groups/{groupId}/reserve
   Body: { schedule_option_id: "..." }
   
   Backend (MongoGroupRepository.reserveSeat()):
   ┌─────────────────────────────────────────────────┐
   │ 1. Verifica group.current_count < max_capacity   │
   │ 2. Calcula PricingEngine.calculate(position)     │
   │ 3. Crea Reservation:                             │
   │    { user_id, group_id, frozen_price,            │
   │      status: PENDING, expires_at: now+5min,     │
   │      schedule_option_id }                        │
   │ 4. Incrementa group.current_count += 1           │
   │ 5. Despacha ReleaseReservationJob (delay: 5min)  │
   └─────────────────────────────────────────────────┘
   
   Frontend recibe: { reservation_id, frozen_price, expires_at }
   → Inicia setInterval() countdown de 5 minutos en pantalla
   → El precio queda "congelado" (frozen_price)

8. SIN confirmar pago → Job se ejecuta a los 5 minutos:
   
   ReleaseReservationJob:
   → Reservation.status = EXPIRED
   → Group.current_count -= 1  (libera asiento)
   
   El estudiante ve en pantalla: "⏰ Tiempo agotado. Reserva cancelada."

9. CON confirmación de pago (dentro de 5 min):
   
   PATCH /api/v1/reservations/{reservationId}/confirm
   
   Backend verifica:
   → reservation.user_id == X-User-Id ✓
   → reservation.status == PENDING ✓
   → reservation.expires_at > now() ✓
   → Actualiza: status = PAID, paid_at = now()
   
   Frontend: Muestra "¡Inscripción exitosa!" y navega a /student/my-courses
```

---

### Ver Mis Cursos

```
10. <MyCourses /> se monta

    GET /api/v1/my-courses
    Header: X-User-Id: {studentId}
    
    Backend: db.reservations.find({ user_id: studentId })
             .populate("group").populate("course")
             .populate schedule winning date
    
    Frontend muestra para cada inscripción:
    ├── Nombre del Curso + Grupo
    ├── Status: PENDING | PAID | EXPIRED
    ├── Precio pagado (frozen_price)
    ├── Barra de progreso: N/5 alumnos
    └── Si group está lleno → muestra "Horario Ganador: [fecha]"
```

---

### Ver Conversión de Divisas (API Externa)

```
11. Durante el Checkout, la interfaz consume una segunda API
    
    GET /api/v1/exchange-rate
    → Backend consume exchangerate-api.com (Cache 24h)
    → Frontend recibe: { rate: 0.058 }
    
    En pantalla se muestra un badge dinámico:
    "≈ $12.34 USD (Precio real en MXN: $200.00)"
    
    Cumple requisito: Middleware + Consumo de 2 APIs funcionales.
```

---

---

## 👨‍🏫 FLUJO DEL PROFESOR

### Acceso al Panel

```
1. Profesor visita /login
   → Ingresa email: vertiz@ep4.edu + rol: Profesor
   
   POST /api/v1/auth/login
   Body: { email: "vertiz@ep4.edu", password: "teacher123" }
   
   Backend verifica credenciales y retorna user con role: "TEACHER"
   → Frontend: login(user) → guarda en localStorage
   → Navega automáticamente a /teacher/dashboard
```

---

### Crear un Curso Borrador

```
2. En <TeacherDashboard />, clic en "Nuevo Curso"
   → Abre modal de creación

3. Profesor llena: Nombre, Descripción → clic "Crear Borrador"
   
   POST /api/v1/teacher/courses
   Headers: X-User-Id: {teacherId}
   Body: { name: "Mi Curso", description: "..." }
   
   Backend (CourseManagementController.store()):
   → Crea Course: { name, description, status: "DRAFT",
                    teacher_id: teacher._id }
   → Crea automáticamente 2-3 Groups asociados al Course
     (Grupo A, Grupo B) con max_capacity: 5, current_count: 0
   
   Frontend: cierra modal, recarga cursos, muestra nueva tarjeta DRAFT
```

---

### Editar Curso (mientras está en DRAFT)

```
4. Clic en "📝 Editar Curso" en la tarjeta DRAFT
   → Abre modal de edición con datos actuales precargados

5. Profesor modifica nombre/descripción → "Guardar Cambios"
   
   PUT /api/v1/teacher/courses/{courseId}
   Body: { name: "Nombre Nuevo", description: "..." }
   
   Backend: valida que course.status === 'DRAFT'
            actualiza en MongoDB
   
   Frontend: cierra modal, recarga dashboard
```

---

### Proponer Fechas de Disponibilidad

```
6. Antes de pedir aprobación, el Profesor debe asignar fechas a cada Grupo
   Clic en "📅 Fechas G1" → abre modal de fechas

7. Selecciona 3 fechas futuras (datetime-local inputs) → "Guardar Opciones"
   
   POST /api/v1/teacher/groups/{groupId}/schedule
   Body: { dates: ["2026-05-10T10:00:00Z", "2026-05-12T...", "..."] }
   
   Backend (ScheduleController.store()):
   → Elimina ScheduleOptions anteriores del grupo (si existían)
   → Crea 3 nuevas ScheduleOption documents con vote_count: 0
   
   Frontend: muestra alerta de éxito, recarga dashboard
```

---

### Solicitar Aprobación del Curso

```
8. Con las fechas asignadas → clic "Pedir Aprobación"
   
   PATCH /api/v1/teacher/courses/{courseId}/submit
   
   Backend: course.status = "PENDING_APPROVAL"
   
   Frontend: la tarjeta cambia badge de "DRAFT" (amarillo)
             a "PENDING_APPROVAL" (azul)
             El botón "Pedir Aprobación" desaparece
```

---

### Ver Panel de Finanzas

```
9. Navega a /teacher/finances

10. <Finances /> carga todos los cursos APPROVED del Profesor
    
    GET /api/v1/teacher/courses           ← Filtra status===APPROVED en frontend
    GET /api/v1/teacher/courses/{id}/subscribers ← Para cada curso
    
    Para cada grupo muestra:
    ├── current_count / max_capacity
    ├── Ingreso acumulado (suma de frozen_price de reservas PAID)
    └── Si current_count < 5:  botón BLOQUEADO (gris, disabled)
        Si current_count === 5: botón VERDE "Descargar Recibo PDF" ✓
    
    La clase queda "inicializada" solo cuando el grupo tiene 5/5 alumnos.
```

---

### Borrado Lógico de Cursos

```
11. Si el curso está en DRAFT o REJECTED, el Profesor puede eliminarlo.
    
    DELETE /api/v1/teacher/courses/{id}
    
    Backend (Trait SoftDeletes):
    → Agrega `deleted_at` timestamp al documento en MongoDB.
    → Eloquent filtra automáticamente los eliminados en futuras consultas.
```

---

## 🛡️ FLUJO DEL ADMINISTRADOR

### Acceso al Panel de Control

```
1. Admin visita /login
   → Email: admin@ep4.edu + Rol: Administrador
   
   POST /api/v1/auth/login
   → Retorna user con role: "ADMIN"
   → Frontend navega a /admin/dashboard
```

---

### Revisar Cursos Pendientes

```
2. <AdminDashboard /> se monta
   
   GET /api/v1/admin/courses/pending
   Header: X-User-Id: {adminId}
   
   Backend: db.courses.find({ status: "PENDING_APPROVAL" })
            .populate("teacher")
   
   Frontend muestra tabla/lista con:
   ├── Badge: "PENDING_APPROVAL" (ámbar)
   ├── Nombre del Profesor
   ├── Nombre y descripción del curso
   ├── Precio calculado estimado
   └── Botones: [✓ Aprobar] [✗ Rechazar]
```

---

### Aprobar un Curso

```
3. Admin hace clic en "✓ Aprobar"
   → Aparece diálogo confirm() nativo del navegador
   → "¿Estás seguro de APROBAR este curso?"
   → Admin confirma: "Aceptar"
   
   PATCH /api/v1/admin/courses/{courseId}/approve
   Header: X-User-Id: {adminId}
   
   Backend (CourseApprovalController.approve()):
   → Verifica course.status === 'PENDING_APPROVAL'
   → Actualiza: { status: "APPROVED", approved_by: adminId, approved_at: now }
   → Crea AuditLog: { action: "COURSE_APPROVED", target_id: courseId }
   
   EFECTO INMEDIATO en el sistema:
   → El curso DESAPARECE de la lista de pendientes del Admin
   → El curso APARECE en el Catálogo Público (GET /api/v1/courses)
   → Los Estudiantes ahora pueden ver y reservar el curso
   → El Profesor puede ver ingresos en su tab de Finanzas
   
   Frontend: Recarga la lista (fetchPendingCourses())
             Muestra "✅ Curso aprobado exitosamente."
```

---

### Rechazar un Curso

```
4. Admin hace clic en "✗ Rechazar"
   → Aparece window.prompt() pidiendo la razón del rechazo
   → Admin escribe: "El título es ambiguo, favor especificar el nivel"
   → Clic "Aceptar"
   
   PATCH /api/v1/admin/courses/{courseId}/reject
   Body: { reason: "El título es ambiguo..." }
   
   Backend:
   → course.status = "REJECTED"
   → course.rejection_reason = reason
   → AuditLog.record("COURSE_REJECTED", ...)
   
   EFECTO en el sistema:
   → El curso NO aparece en el Catálogo Público
   → El Profesor verá la tarjeta con badge "REJECTED" en su Dashboard
   → El Profesor puede editar y volver a enviar a revisión
   
   Frontend: Muestra "❌ Curso devuelto al profesor."
             Recarga la lista de pendientes
```

---

## 🔄 Flujo Transversal Completo (End-to-End)

Este es el ciclo completo de vida de un curso desde su creación hasta la clase impartida:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                               │
│  TEACHER                   ADMIN                   STUDENTS (5)              │
│    │                         │                         │                     │
│    │ Crea Curso (DRAFT)       │                         │                     │
│    ├──────────────────────────────────────────────────►│ (no visible aún)   │
│    │                         │                         │                     │
│    │ Propone 3 fechas         │                         │                     │
│    │ (ScheduleOptions)        │                         │                     │
│    │                         │                         │                     │
│    │ Pide Aprobación          │                         │                     │
│    │────────────────────────►│                         │                     │
│    │                         │                         │                     │
│    │                         │ Revisa y APRUEBA        │                     │
│    │◄────────────────────────│                         │                     │
│    │    (APPROVED)           │                         │                     │
│    │                         │     ┌───────────────────┤                     │
│    │                         │     │ Catálogo visible   │                     │
│    │                         │     │ para Estudiantes  │                     │
│    │                         │     └───────────────────┤                     │
│    │                         │                         │                     │
│    │                         │     Estudiante 1 reserva y paga ($200)       │
│    │                         │     Estudiante 2 reserva y paga ($170)       │
│    │                         │     Estudiante 3 reserva y paga ($142)       │
│    │                         │     Estudiante 4 reserva y paga ($118)       │
│    │                         │     Estudiante 5 reserva y paga ($98)        │
│    │                         │                         │                     │
│    │ current_count = 5/5 ────────────────────────────►│ CLASE INICIALIZADA │
│    │                                                   │                     │
│    │ Sistema determina Horario Ganador por votos        │                     │
│    │                                                   │                     │
│    │ Descarga Recibo PDF ($728 total)                  │                     │
│    │ (botón se desbloquea en /teacher/finances)        │                     │
│    │                                                   │                     │
│   CLASE SE IMPARTE el día del horario ganador          │                     │
│                                                                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Estados de una Reserva (Reservation)

```
             PENDING ──────────────────────────────► PAID
          (reserve())                              (confirm())
               │                                      │
               │ (5 min pasan sin confirmar)           │
               ▼                                       │
            EXPIRED                                    │
       (ReleaseReservationJob)                    "Inscrito"
       current_count -= 1                    current_count se mantiene
```

---

## Resumen de Reglas de Negocio Críticas

| Regla | Implementación |
|---|---|
| Máximo 5 alumnos por grupo | `Group.max_capacity = 5`, verificado en `reserveSeat()` |
| Precio decrece con cada alumno | `PricingEngine` (decaimiento exponencial) en backend y frontend |
| Reserva caduca en 5 minutos | `expires_at = now + 5min` + `ReleaseReservationJob` |
| Solo cursos APROBADOS en catálogo | `Course::approved()` scope filtra por `status=APPROVED` |
| Recibo PDF solo si grupo lleno | Frontend verifica `current_count === max_capacity` antes de habilitar botón |
| Admin no puede aprobar un DRAFT | Backend verifica `status === PENDING_APPROVAL` antes de hacer `approve()` |
| Profesor no puede editar un curso APPROVED | Backend + Frontend solo muestran edición en DRAFT |
| Un estudiante vota una sola vez | Unique index en `{group_id, user_id}` en `schedule_votes` |
| Avatar de Iniciales | Generado dinámicamente en frontend basado en `user.name` |
| Sincronización Teacher | Actualización de `Profile` replica datos en colección `teachers` |

---

## 👤 FLUJO COMPARTIDO: GESTIÓN DE PERFIL

Este flujo es idéntico para **Student, Teacher y Admin**.

```
1. Usuario hace clic en "Mi Perfil" en la Navbar/Sidebar
   → Navega a /profile (o /role/profile)

2. <Profile /> se monta:
   GET /api/v1/profile
   Backend: Retorna User { name, email, phone, role } y carga relación teacher si aplica.

3. Usuario actualiza datos:
   PUT /api/v1/profile
   Body: { name, email, phone, specialty?, password? }

   Backend (ProfileController@update):
   a) Valida Name, Email (único), Phone y Specialty (si es teacher).
   b) Actualiza User en MongoDB.
   c) Si es Teacher: Sincroniza Specialty y Phone en la colección de Teachers.
   d) Si password está presente: Hashea y actualiza credencial.

4. Frontend:
   → Actualiza AuthContext para reflejar el nuevo nombre/email globalmente.
   → Muestra alerta de éxito: "Perfil actualizado correctamente."
```
