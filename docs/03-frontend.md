# 03 — Frontend: React 18 + Vite

## Introducción

El frontend de MicroCohorts es una **Single Page Application (SPA)** construida en React 18 con Vite como herramienta de desarrollo. Es un cliente completamente desacoplado del servidor que consume la API REST del backend Laravel via `fetch` nativo del navegador.

---

## Configuración e Inicio

```bash
# Desde la carpeta raíz del proyecto
cd client
npm install
npm run dev
# Servidor en: http://localhost:5174
```

El archivo `client/vite.config.js` configura el puerto y el plugin de React:

```js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: { port: 5174 }
})
```

---

## Estructura de Directorios (`client/src/`)

```
src/
│
├── main.jsx                 ← Punto de entrada: monta <App /> en el DOM
├── App.jsx                  ← Router principal con todas las rutas
├── index.css                ← Sistema de diseño global (CSS variables, clases)
│
├── context/
│   └── AuthContext.jsx      ← Estado global de autenticación
│
├── components/
│   └── layouts/
│       ├── StudentLayout.jsx ← Layout con barra lateral para Estudiante
│       ├── TeacherLayout.jsx ← Layout con barra lateral para Profesor
│       └── AdminLayout.jsx   ← Layout con barra lateral para Administrador
│
└── pages/
    ├── Landing.jsx          ← Página de inicio pública
    ├── Login.jsx            ← Formulario de login
    ├── Register.jsx         ← Formulario de registro
    ├── student/
    │   ├── Catalog.jsx      ← Catálogo de cursos disponibles
    │   ├── Checkout.jsx     ← Proceso de reserva y pago
    │   └── MyCourses.jsx    ← Mis suscripciones y cursos activos
    ├── teacher/
    │   ├── Dashboard.jsx    ← Panel de cursos del Profesor
    │   └── Finances.jsx     ← Panel de finanzas y recibos
    └── admin/
        └── Dashboard.jsx    ← Panel de aprobación de Admin
```

---

## Sistema de Autenticación en el Frontend

### `AuthContext.jsx`

El contexto de autenticación es el **corazón del estado global** de la app. Provee a todos los componentes el acceso al usuario actual y los métodos de login/logout.

```jsx
const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Al cargar la app, restaura sesión desde localStorage
    const savedUser = localStorage.getItem('ep4_user');
    if (savedUser) setUser(JSON.parse(savedUser));
    setLoading(false);
  }, []);

  const login = (userData) => {
    setUser(userData);
    localStorage.setItem('ep4_user', JSON.stringify(userData)); // Persistencia
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem('ep4_user');
  };

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};
```

**Persistencia de Sesión:** El objeto `user` completo (incluyendo `_id` y `role`) se guarda en `localStorage` bajo la clave `ep4_user`. Si el usuario cierra y reabre el navegador, la sesión se restaura automáticamente.

---

## Enrutamiento (`App.jsx`)

El archivo `App.jsx` define el árbol completo de rutas usando **React Router DOM v6**:

```jsx
// Componente guardián de rutas protegidas
const ProtectedRoute = ({ children, allowedRole }) => {
  const { user, loading } = useAuth();
  if (loading) return <div>Loading...</div>;
  if (!user) return <Navigate to="/login" replace />;      // Sin sesión → Login
  if (allowedRole && user.role !== allowedRole)             // Rol incorrecto → Home
    return <Navigate to="/" replace />;
  return children;
};
```

### Mapa de Rutas

| Path | Componente | Protección |
|---|---|---|
| `/` | `Landing` | Pública |
| `/login` | `Login` | Pública |
| `/register` | `Register` | Pública |
| `/student/courses` | `Catalog` | Solo `STUDENT` |
| `/student/my-courses` | `MyCourses` | Solo `STUDENT` |
| `/student/checkout/:groupId` | `Checkout` | Solo `STUDENT` |
| `/teacher/dashboard` | `TeacherDashboard` | Solo `TEACHER` |
| `/teacher/finances` | `Finances` | Solo `TEACHER` |
| `/admin/dashboard` | `AdminDashboard` | Solo `ADMIN` |
| `*` (cualquier otra) | Redirect a `/` | — |

---

## Sistema de Diseño (`index.css`)

La UI usa **Vanilla CSS** con variables CSS custom properties para mantener consistencia. El tema es oscuro (dark mode) con estética **Glassmorphism**.

### Variables de Color Principales

```css
:root {
  --bg-dark: #0a0a0f;          /* Fondo principal */
  --bg-card: rgba(255,255,255,0.03);  /* Cards */
  --primary: #6366f1;          /* Indigo — color de acción */
  --primary-glow: rgba(99,102,241,0.3);
  --success: #10b981;          /* Verde */
  --warning: #f59e0b;          /* Ámbar — usado en Admin */
  --danger: #ef4444;           /* Rojo */
  --text-main: #e2e8f0;        /* Texto principal */
  --text-muted: #94a3b8;       /* Texto secundario */
  --border-glass: rgba(255,255,255,0.08);
}
```

### Clases de Utilidad Reutilizables

```css
.glass-panel { /* Panel con efecto glassmorphism */
  background: rgba(255,255,255,0.03);
  backdrop-filter: blur(20px);
  border: 1px solid var(--border-glass);
  border-radius: var(--radius-lg);
}
.btn-primary { background: var(--primary); color: white; }
.btn-outline  { border: 1px solid var(--border-glass); }
.badge-success { background: rgba(16,185,129,0.2); color: var(--success); }
.badge-warning { background: rgba(245,158,11,0.2); color: var(--warning); }
.title { /* Gradiente en el texto del título */
  background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
```

---

## Componentes de Layout (Layouts)

Cada rol tiene su propio **Layout Shell** que provee:
1. Una **barra lateral** con links de navegación.
2. El área de contenido principal (`<Outlet />`) donde React Router inyecta la vista activa.
3. Un **botón de logout** que limpia la sesión y redirecciona.

### `StudentLayout.jsx`
- Links: `Catálogo de Cursos` → `/student/courses` | `Mis Cursos` → `/student/my-courses`
- Color de acento: Índigo (`--primary`)

### `TeacherLayout.jsx`
- Links: `Mis Cursos` → `/teacher/dashboard` | `Finanzas y Clases` → `/teacher/finances`
- Importa los íconos `Presentation`, `Activity`, `Users`, `LogOut` de `lucide-react`

### `AdminLayout.jsx`
- Links: `Aprobación de Cursos` → `/admin/dashboard`
- Color de acento: Ámbar/Gold (`--warning`) para comunicar autoridad

---

## Páginas — Descripción Detallada

### `Login.jsx`
El formulario tiene tres campos: `email`, `password` (auto-asignada por rol) y un `<select>` de rol (STUDENT/TEACHER/ADMIN).

Tras un login exitoso, el componente llama a `login(userData)` del contexto y navega al panel correspondiente al rol:

```jsx
if (realUser.role === 'STUDENT') navigate('/student/courses');
else if (realUser.role === 'TEACHER') navigate('/teacher/dashboard');
else navigate('/admin/dashboard');
```

### `Catalog.jsx` (Student)
- Llama `GET /api/v1/courses` al montar el componente.
- Cada curso tiene grupos; para cada grupo calcula el precio actual localmente usando la fórmula de decaimiento exponencial (replicación del `PricingEngine` del backend para UI en tiempo real).
- El botón "Reservar" navega a `/student/checkout/:groupId`.

### `Checkout.jsx` (Student)
Es la vista más compleja del frontend. Tiene varios estados:
1. **Carga**: Llama `GET /api/v1/groups/{groupId}/pricing` para obtener el precio justo al entrar.
2. **Selección**: Llama `GET /api/v1/groups/{groupId}/schedule` para mostrar los horarios disponibles.
3. **Reserva**: El estudiante selecciona un horario y hace clic en "Confirmar Reserva" → `POST /api/v1/groups/{groupId}/reserve`.
4. **Timer**: Inicia un `setInterval` de 5 minutos mostrando cuenta regresiva.
5. **Pago**: Botón "Confirmar Pago" → `PATCH /api/v1/reservations/{id}/confirm`.
6. **Éxito**: Muestra confirmación y libera el timer.

### `MyCourses.jsx` (Student)
- Llama `GET /api/v1/my-courses`.
- Muestra cada reservación con su status (`PENDING`, `PAID`, `EXPIRED`).
- Si el grupo está lleno y tiene votos, muestra el "Horario Ganador".

### `Dashboard.jsx` (Teacher)
- Carga todos sus cursos + suscriptores al montar.
- **Modo DRAFT**: Muestra botones "Editar Curso", "Proponer Fechas por Grupo", "Pedir Aprobación".
- **Modo APPROVED**: Muestra "Ver Resultados de Votación".
- Modales: Crear Curso, Editar Curso, Proponer 3 Fechas.

### `Finances.jsx` (Teacher)
- Filtra solo los cursos `APPROVED`.
- Para cada grupo, muestra `current_count / max_capacity`.
- Si `current_count < 5`: botón "Bloqueado" (deshabilitado).
- Si `current_count === 5` (clase llena): botón verde "Descargar Recibo PDF" activo.

### `Dashboard.jsx` (Admin)
- Llama `GET /api/v1/admin/courses/pending`.
- Muestra cada curso `PENDING_APPROVAL` con nombre, descripción y profesor.
- "Aprobar" → `PATCH /api/v1/admin/courses/{id}/approve`.
- "Rechazar" → Pide razón con `window.prompt()` → `PATCH /api/v1/admin/courses/{id}/reject`.
