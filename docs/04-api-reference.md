# 04 — Referencia de la API REST

**Base URL:** `http://127.0.0.1:8000/api/v1`  
**Formato:** JSON  
**Autenticación:** Header `X-User-Id: {mongoObjectId}` (en rutas protegidas)

---

## Convención de Respuestas

Todas las respuestas siguen el mismo envelope JSON:

```json
{
  "data": { ... },         // Payload principal
  "message": "Texto...",   // Mensaje human-readable
  "status": "success"      // "success" | "error"
}
```

Los errores de validación incluyen adicionalmente:
```json
{
  "errors": {
    "campo": ["El campo es requerido."]
  }
}
```

---

## 🔓 Autenticación (Público)

### `POST /api/v1/auth/register`

Registra un nuevo usuario.

**Body:**
```json
{
  "name": "Juan García",
  "email": "juan@example.com",
  "password": "secret123",
  "role": "STUDENT"
}
```

**Respuesta 201:**
```json
{
  "data": {
    "user": {
      "id": "6616a2f...",
      "name": "Juan García",
      "email": "juan@example.com",
      "role": "STUDENT"
    }
  },
  "message": "Usuario registrado exitosamente.",
  "status": "success"
}
```

---

### `POST /api/v1/auth/login`

Inicia sesión.

**Body:**
```json
{
  "email": "student@ep4.edu",
  "password": "student123"
}
```

**Respuesta 200:**
```json
{
  "data": {
    "user": {
      "id": "6616a2f...",
      "name": "Estudiante Demo",
      "email": "student@ep4.edu",
      "role": "STUDENT"
    },
    "token": null
  },
  "message": "Login exitoso.",
  "status": "success"
}
```

**Errores:**
- `422` - Credenciales incorrectas
- `401` - Usuario no encontrado

---

## 📚 Catálogo (Público)

### `GET /api/v1/courses`

Lista todos los cursos con status `APPROVED`.

**Respuesta 200:**
```json
{
  "data": [
    {
      "_id": "6616b...",
      "name": "Taller de React Avanzado",
      "description": "...",
      "status": "APPROVED",
      "teacher": { "name": "Prof. Vértiz", ... },
      "groups": [
        {
          "_id": "6617c...",
          "name": "Grupo A",
          "current_count": 2,
          "max_capacity": 5,
          "schedule_options": [...]
        }
      ]
    }
  ],
  "status": "success"
}
```

### `GET /api/v1/courses/{courseId}`

Detalle de un curso específico.

---

## 🎓 Estudiante (Requiere `X-User-Id` + Rol `STUDENT`)

### `GET /api/v1/groups/{groupId}/pricing`

Calcula el precio actual para el próximo asiento del grupo.

**Respuesta 200:**
```json
{
  "data": {
    "group": {
      "id": "...",
      "current_count": 2,
      "max_capacity": 5,
      "your_position": 3
    },
    "pricing": {
      "position": 3,
      "base_price": 200,
      "final_price": 142.32,
      "discount_percentage": 28.84,
      "formula": "200 * e^(-0.25 * 2)"
    }
  }
}
```

---

### `GET /api/v1/groups/{groupId}/schedule`

Lista las opciones de horario disponibles para votar.

**Respuesta 200:**
```json
{
  "data": [
    {
      "_id": "option1...",
      "proposed_date": "2026-05-10T10:00:00Z",
      "vote_count": 1,
      "user_voted": false
    },
    {
      "_id": "option2...",
      "proposed_date": "2026-05-15T14:00:00Z",
      "vote_count": 0,
      "user_voted": false
    }
  ]
}
```

---

### `POST /api/v1/groups/{groupId}/reserve`

Reserva un asiento en el grupo. Inicia el timer de 5 minutos TTL.

**Headers:** `X-User-Id: {userId}`

**Body:**
```json
{
  "schedule_option_id": "6617d..."
}
```

**Respuesta 201:**
```json
{
  "data": {
    "reservation_id": "6618e...",
    "group_id": "6617c...",
    "status": "PENDING",
    "frozen_price": 142.32,
    "expires_at": "2026-04-12T05:15:00Z"
  },
  "message": "Reservación creada. Tienes 5 minutos para confirmar el pago.",
  "status": "success"
}
```

**Errores:**
- `409` - Grupo lleno (`GroupFullException`)
- `409` - El estudiante ya tiene una reserva activa

---

### `POST /api/v1/groups/{groupId}/vote`

Registra el voto del estudiante por una opción de horario.

**Body:**
```json
{
  "schedule_option_id": "6617d..."
}
```

**Respuesta 201:**
```json
{
  "message": "Voto registrado exitosamente.",
  "status": "success"
}
```

**Errores:**
- `409` - El estudiante ya votó en este grupo

---

### `PATCH /api/v1/reservations/{reservationId}/confirm`

Confirma el pago de una reserva (simula el procesamiento del pago).

**Respuesta 200:**
```json
{
  "data": {
    "reservation_id": "6618e...",
    "status": "PAID",
    "frozen_price": 142.32,
    "paid_at": "2026-04-12T05:13:00Z"
  },
  "message": "¡Pago confirmado exitosamente! Ya estás inscrito.",
  "status": "success"
}
```

**Errores:**
- `409` - La reserva no está en estado PENDING
- `410` - La reserva expiró (TTL de 5 min superado)

---

### `GET /api/v1/my-courses`

Lista todas las reservas del estudiante.

**Respuesta 200:**
```json
{
  "data": [
    {
      "reservation_id": "6618e...",
      "status": "PAID",
      "frozen_price": 142.32,
      "paid_at": "...",
      "course": { "name": "Taller de React..." },
      "group": {
        "name": "Grupo A",
        "current_count": 5,
        "max_capacity": 5
      },
      "winning_date": "2026-05-10T10:00:00Z"
    }
  ]
}
```

---

### `GET /api/v1/reservations/{reservationId}/receipt`

Descarga el recibo de una reserva pagada.

---

## 👨‍🏫 Profesor (Requiere `X-User-Id` + Rol `TEACHER`)

### `GET /api/v1/teacher/courses`

Lista todos los cursos del profesor autenticado.

---

### `POST /api/v1/teacher/courses`

Crea un nuevo curso en estado DRAFT.

**Body:**
```json
{
  "name": "Mi Nuevo Curso",
  "description": "Descripción del curso..."
}
```

---

### `PUT /api/v1/teacher/courses/{courseId}`

Actualiza nombre y descripción de un curso DRAFT.

**Body:**
```json
{
  "name": "Nombre Actualizado",
  "description": "Nueva descripción"
}
```

---

### `PATCH /api/v1/teacher/courses/{courseId}/submit`

Envía el curso a revisión del Admin (`DRAFT → PENDING_APPROVAL`).

---

### `GET /api/v1/teacher/courses/{courseId}/subscribers`

Lista los estudiantes suscritos a cada grupo del curso.

**Respuesta 200:**
```json
{
  "data": {
    "groups_summary": [
      {
        "id": "6617c...",
        "name": "Grupo A",
        "current_count": 3,
        "max_capacity": 5
      }
    ],
    "subscribers": [
      {
        "user_name": "Juan García",
        "group_id": "6617c...",
        "frozen_price": 150.00,
        "status": "PAID"
      }
    ]
  }
}
```

---

### `POST /api/v1/teacher/groups/{groupId}/schedule`

Propone entre 3 y 5 fechas de disponibilidad para el grupo.

**Body:**
```json
{
  "dates": [
    "2026-05-10T10:00:00Z",
    "2026-05-12T14:00:00Z",
    "2026-05-15T18:00:00Z"
  ]
}
```

---

### `GET /api/v1/teacher/groups/{groupId}/votes`

Obtiene los resultados de votación de un grupo.

### `GET /api/v1/teacher/groups/{groupId}/winning-date`

Obtiene la fecha ganadora (la más votada).

---

## 🛡️ Administrador (Requiere `X-User-Id` + Rol `ADMIN`)

### `GET /api/v1/admin/courses/pending`

Lista todos los cursos en estado `PENDING_APPROVAL`.

---

### `GET /api/v1/admin/courses?status=APPROVED`

Lista todos los cursos, opcionalmente filtrados por status.

---

### `PATCH /api/v1/admin/courses/{courseId}/approve`

Aprueba un curso (`PENDING_APPROVAL → APPROVED`). Registra en AuditLog.

**Respuesta 200:**
```json
{
  "data": { "status": "APPROVED", ... },
  "message": "Curso aprobado exitosamente.",
  "status": "success"
}
```

**Errores:**
- `409` - El curso no está en `PENDING_APPROVAL`

---

### `PATCH /api/v1/admin/courses/{courseId}/reject`

Rechaza un curso (`PENDING_APPROVAL → REJECTED`).

**Body:**
```json
{
  "reason": "El título es confuso y no describe el contenido."
}
```

---

### `GET /api/v1/admin/users`

Lista todos los usuarios del sistema.

### `PATCH /api/v1/admin/users/{userId}/toggle-status`

Activa o desactiva un usuario.

### `GET /api/v1/admin/reports/revenue`

Reporte de ingresos totales de la plataforma.

### `GET /api/v1/admin/audit-log`

Historial de acciones administrativas.

---

## Códigos de Estado HTTP Usados

| Código | Significado | Cuándo se usa |
|---|---|---|
| `200` | OK | Operación exitosa |
| `201` | Created | Recurso creado (reserva, curso, voto) |
| `401` | Unauthorized | Falta header `X-User-Id` o usuario no existe |
| `403` | Forbidden | Rol incorrecto para la ruta |
| `404` | Not Found | Recurso no encontrado en MongoDB |
| `409` | Conflict | Estado incorrecto (ej. grupo lleno, ya votó) |
| `410` | Gone | Reserva expirada |
| `422` | Unprocessable Entity | Error de validación del body |
