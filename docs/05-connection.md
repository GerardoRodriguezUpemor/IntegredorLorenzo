# 05 — Conexión Frontend ↔ Backend

## Arquitectura de Comunicación

El frontend y el backend son **completamente independientes** entre sí. Se comunican exclusivamente a través de **HTTP/JSON** usando la `fetch` API nativa del navegador.

```
┌─────────────────────────────────────────────────────────┐
│                 React SPA (puerto 5174)                  │
│                                                           │
│   const response = await fetch(                          │
│     'http://127.0.0.1:8000/api/v1/...',                  │
│     {                                                     │
│       headers: {                                          │
│         'Accept': 'application/json',                    │
│         'Content-Type': 'application/json',              │
│         'X-User-Id': user._id   ← ID del usuario         │
│       }                                                   │
│     }                                                     │
│   );                                                     │
│                                                           │
└────────────────────────┬────────────────────────────────┘
                         │  HTTP Request
                         ▼
┌─────────────────────────────────────────────────────────┐
│              Laravel API (puerto 8000)                    │
│                                                           │
│   Routing → Middleware → Controller → Response JSON       │
└─────────────────────────────────────────────────────────┘
```

---

## El Puente de Identidad: Header `X-User-Id`

La pieza más crítica de la conexión es el header `X-User-Id`. Este header es el **mecanismo de autenticación** que sustituye a los tokens en esta implementación de desarrollo.

### Cómo funciona paso a paso:

**1. Login (Frontend → Backend):**
```
POST http://127.0.0.1:8000/api/v1/auth/login
Body: { email, password }
```

**2. Respuesta del Backend:**
```json
{
  "data": {
    "user": { "id": "6616a2f9b...", "name": "...", "role": "STUDENT" }
  }
}
```

**3. El Frontend guarda el `id` en localStorage:**
```jsx
// Login.jsx
const realUser = {
  ...data.data.user,
  _id: data.data.user.id  // Normaliza el campo
};
login(realUser); // → guarda en localStorage como "ep4_user"
```

**4. Cada llamada protegida incluye el ID:**
```jsx
// Catalog.jsx, Checkout.jsx, MyCourses.jsx, etc.
const { user } = useAuth();

const res = await fetch('http://127.0.0.1:8000/api/v1/courses', {
  headers: {
    'Accept': 'application/json',
    'X-User-Id': user._id   // ← El ID que se guardó al hacer login
  }
});
```

**5. Laravel valida en el Middleware (`SimpleAuth.php`):**
```php
$userId = $request->header('X-User-Id');    // Extrae el header
$user = User::find($userId);               // Busca en MongoDB
$request->attributes->set('authenticated_user', $user); // Inyecta en el request
```

**6. El Controlador usa el usuario inyectado:**
```php
// Cualquier controlador protegido
$user = $request->attributes->get('authenticated_user');
// Ya tiene acceso al objeto User completo
```

---

## CORS (Cross-Origin Resource Sharing)

Como el frontend está en `localhost:5174` y el backend en `localhost:8000`, los navegadores aplican la política **Same-Origin Policy** que bloquea las peticiones de origen diferente por defecto.

Laravel maneja CORS a través del archivo `config/cors.php`:

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],  // En producción: solo el dominio del frontend
    'allowed_headers' => ['*'],  // Permite el header X-User-Id
];
```

Esto le indica al navegador que el origen `localhost:5174` está permitido para hacer peticiones al servidor.

---

## Patrón de Fetch: Anatomía de una Llamada API

Todos los calls al API siguen exactamente el mismo patrón en el frontend:

```jsx
const fetchData = async () => {
  setLoading(true);   // 1. Activar estado de carga
  try {
    const res = await fetch('http://127.0.0.1:8000/api/v1/endpoint', {
      method: 'GET',   // o POST, PATCH, PUT
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',  // Solo en POST/PUT
        'X-User-Id': user._id,              // En rutas protegidas
      },
      body: JSON.stringify(payload),         // Solo en POST/PUT
    });

    const data = await res.json();           // 2. Parsear JSON

    if (!res.ok) {
      setError(data.message);               // 3a. Manejar error del servidor
      return;
    }

    setState(data.data);                    // 3b. Actualizar estado con datos exitosos

  } catch (err) {
    setError('Error de conexión');          // 4. Network error (servidor caído)
  } finally {
    setLoading(false);                      // 5. Siempre desactiva el loading
  }
};
```

---

## Flujo de Datos: El Catálogo

Ejemplo concreto del flujo completo de datos para la página de catálogo:

```
1. USUARIO abre http://localhost:5174/student/courses
                    │
                    ▼
2. React monta <Catalog /> y ejecuta useEffect()
                    │
                    ▼
3. FETCH: GET http://127.0.0.1:8000/api/v1/courses
   Headers: X-User-Id: 6616a2f9b...
                    │
                    ▼
4. LARAVEL: api.php → CatalogController::index()
   → Course::approved()->with('groups', 'teacher')->get()
   → MongoDB Query: db.courses.find({ status: "APPROVED" })
                    │
                    ▼
5. RESPUESTA JSON regresa al navegador:
   { data: [ { _id, name, groups: [...], teacher: {...} } ] }
                    │
                    ▼
6. React actualiza el estado: setCourses(data.data)
                    │
                    ▼
7. React re-renderiza: muestra las tarjetas de cursos
```

---

## Flujo del Checkout: La Operación más Compleja

El checkout involucra **4 llamadas API secuenciales**:

```
PASO 1: Obtener precio actual
GET /api/v1/groups/{groupId}/pricing
→ Frontend muestra: "Tu precio: $142.32 (3er lugar)"

PASO 2: Obtener opciones de horario
GET /api/v1/groups/{groupId}/schedule
→ Frontend muestra: 3 opciones de fecha con selector

PASO 3: Reservar asiento (estudiante selecciona horario)
POST /api/v1/groups/{groupId}/reserve
Body: { schedule_option_id: "..." }
→ Backend: incrementa current_count del grupo
→ Backend: crea Reservation con status=PENDING, expires_at=now+5min
→ Backend: despacha ReleaseReservationJob con delay 5min
→ Frontend: recibe reservation_id, inicia countdown de 5 minutos

PASO 4: Confirmar pago (dentro de los 5 minutos)
PATCH /api/v1/reservations/{reservationId}/confirm
→ Backend: verifica que reservation.expires_at > now
→ Backend: cambia status PENDING → PAID
→ Frontend: muestra "¡Inscripción exitosa!"
```

Si el estudiante **NO confirma en 5 minutos**:
```
ReleaseReservationJob (scheduled job) se ejecuta:
→ Reservation.status = EXPIRED
→ Group.current_count -= 1 (libera el asiento)
```

---

## Manejo de Errores en la Interfaz

El frontend muestra diferentes estados visuales según la respuesta:

| Estado HTTP | Qué muestra el Frontend |
|---|---|
| `200/201` | Datos en pantalla, toast/alert de éxito |
| `401` | Redirección automática a `/login` |
| `403` | Redirección a `/` (home) |
| `404` | Mensaje "No encontrado" |
| `409` | Alert con el menssaje del backend (`data.message`) |
| `410` | Alert "La reservación ha expirado" |
| `422` | Muestra errores de validación campo por campo |
| Network Error | Alert "Error conectando al servidor" |

---

## Variables de Entorno y Configuración

### Backend (`.env`)
```dotenv
MONGODB_URI=mongodb://localhost:27017
DB_DATABASE=microcohorts
APP_URL=http://127.0.0.1:8000
```

### Frontend

Las URLs del API están **hardcodeadas** directamente en los componentes como:
```jsx
const API_BASE = 'http://127.0.0.1:8000/api/v1';
```

> **Para producción:** Se recomienda crear un archivo `.env` en `/client/` con `VITE_API_URL=https://api.tudominio.com` y referenciarlo en el código como `import.meta.env.VITE_API_URL`.

---

## Ciclo Completo de Comunicación: Resumen Visual

```
FRONTEND                                    BACKEND
   │                                           │
   │──── POST /auth/login ─────────────────►  │
   │◄─── { user: { id, role } } ────────────  │
   │  guarda user._id en localStorage         │
   │                                           │
   │──── GET /courses ──────────────────────► │
   │     Header: X-User-Id: {id}              │
   │◄─── { data: [cursos...] } ─────────────  │
   │  setCourses(data.data)                   │
   │  renderiza cards                         │
   │                                           │
   │──── GET /groups/{id}/pricing ─────────►  │
   │◄─── { pricing: { final_price... } } ───  │
   │  muestra precio                          │
   │                                           │
   │──── POST /groups/{id}/reserve ────────►  │
   │     Body: { schedule_option_id }         │
   │◄─── { reservation_id, expires_at } ────  │
   │  inicia timer de 5 min                   │
   │                                           │
   │──── PATCH /reservations/{id}/confirm ►   │
   │◄─── { status: "PAID" } ────────────────  │
   │  muestra éxito, redirige a /my-courses   │
   │                                           │
```
