# 01 — Visión General del Proyecto MicroCohorts

## ¿Qué es MicroCohorts?

**MicroCohorts** es una plataforma de marketplace educativo de nicho que implementa el concepto de **micro-grupos de aprendizaje cerrado**. A diferencia de los MOOCs masivos (Udemy, Coursera), MicroCohorts opera bajo el modelo de:

- **Cupos cerrados y limitados**: Máximo 5 estudiantes por grupo/cohorte.
- **Precio dinámico decreciente**: El precio del lugar en la clase baja a medida que más estudiantes se suman (Yield Management / Decaimiento Exponencial).
- **Votación democrática de horarios**: Los estudiantes eligen colaborativamente el horario final de la clase.
- **Clase inicializada solo cuando se llena**: El Profesor solo tiene garantizado el pago y puede impartir la sesión cuando el cupo está 100% completo (5/5).

---

## Stack Tecnológico

### Backend
| Tecnología | Versión | Rol |
|---|---|---|
| **PHP** | 8.2+ | Lenguaje de programación |
| **Laravel** | 13.x | Framework MVC / API REST |
| **MongoDB** | 7.x | Base de datos NoSQL orientada a documentos |
| **mongodb/laravel-mongodb** | 5.x | Driver ODM para Laravel |
| **Laravel Queue** | Sync/DB | Jobs asíncronos (liberación de reservas) |
| **PHPUnit** | 11.x | Framework de pruebas unitarias e integración |

### Frontend
| Tecnología | Versión | Rol |
|---|---|---|
| **React** | 18.x | Librería de UI declarativa |
| **Vite** | 5.x | Build tool y dev server ultra-rápido |
| **React Router DOM** | 6.x | Enrutamiento SPA del lado del cliente |
| **lucide-react** | Latest | Librería de íconos SVG |
| **Vanilla CSS** | — | Estilos personalizados (Glassmorphism, dark mode) |
| **dompdf** | Latest | Generación de reportes PDF en el servidor |

---

## Módulos Core (EP4 Implementation)

1. **Motor de Búsqueda y Filtros**: Implementación de búsqueda por nombre y filtros de disponibilidad en tiempo real para el catálogo.
2. **Generador de Recibos Dinámicos**: Servicio que inyecta datos de votación (horario ganador) en comprobantes PDF para el estudiante.
3. **Reportes de Cohorte**: Sistema de generación de reportes financieros para profesores cuando los grupos alcanzan el cupo (5/5).
4. **Perfil Unificado**: Sistema multi-rol con avatares por iniciales, gestión de contacto (teléfono) y seguridad.

---

## Arquitectura General

```
┌─────────────────────────────────────────────────────────────────┐
│                     CLIENTE (Navegador Web)                      │
│                                                                   │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │              React SPA (Vite)                            │   │
│   │   localhost:5174                                          │   │
│   │                                                           │   │
│   │   ┌──────────┐  ┌──────────┐  ┌──────────┐             │   │
│   │   │ /student │  │ /teacher │  │  /admin  │             │   │
│   │   └──────────┘  └──────────┘  └──────────┘             │   │
│   │                                                           │   │
│   └───────────────────────┬─────────────────────────────────┘   │
│                            │  HTTP + JSON (fetch API)             │
│                            │  Header: X-User-Id                   │
└────────────────────────────┼─────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                   SERVIDOR (Laravel 13)                          │
│                                                                   │
│   127.0.0.1:8000/api/v1/...                                      │
│                                                                   │
│   ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│   │  Middleware  │  │  Controllers │  │    Domain    │         │
│   │  SimpleAuth  │  │  API Routes  │  │    Layer     │         │
│   │  RoleGuard   │  │              │  │  PricingEng  │         │
│   └──────────────┘  └──────────────┘  │  VotingEng  │         │
│                                        │  BookingEng │         │
│                                        └──────────────┘         │
│                             │                                     │
│                             ▼                                     │
│                    ┌────────────────┐                            │
│                    │   MongoDB 7    │                            │
│                    │  (NoSQL / ODM) │                            │
│                    └────────────────┘                            │
└─────────────────────────────────────────────────────────────────┘
```

---

## Estructura de Directorios (Raíz del Proyecto)

```
IntegredorLorenzo/                  ← Raíz del monorepo
│
├── app/                            ← Código de la app Laravel (PHP)
│   ├── Domain/                     ← Lógica de negocio pura (DDD)
│   │   ├── Booking/                ← Reservas y cupos
│   │   ├── Pricing/                ← Motor de precios dinámicos
│   │   ├── Scheduling/             ← Motor de votación y scheduling
│   │   └── Shared/                 ← Enums y contratos compartidos
│   ├── Http/
│   │   ├── Controllers/Api/        ← Controladores REST por rol
│   │   └── Middleware/             ← SimpleAuth, RoleMiddleware
│   ├── Infrastructure/             ← Repositorios (MongoDB)
│   ├── Jobs/                       ← Jobs asíncronos (ReleaseReservation)
│   └── Models/                     ← Modelos MongoDB (ODM)
│
├── routes/
│   └── api.php                     ← Definición de TODAS las rutas REST
│
├── database/
│   └── seeders/                    ← Datos iniciales de prueba
│
├── tests/                          ← Suite de pruebas PHPUnit
│
├── client/                         ← Frontend React + Vite
│   ├── src/
│   │   ├── context/                ← React Context (AuthContext)
│   │   ├── components/
│   │   │   └── layouts/            ← Layout compartidos por rol
│   │   ├── pages/
│   │   │   ├── admin/              ← Vistas del Administrador
│   │   │   ├── student/            ← Vistas del Estudiante
│   │   │   └── teacher/            ← Vistas del Profesor
│   │   ├── App.jsx                 ← Router principal
│   │   └── index.css               ← Sistema de diseño global
│   └── vite.config.js
│
└── docs/                           ← 📖 Esta documentación
```

---

## Roles del Sistema

| Rol | Email de Prueba | Contraseña | Panel |
|---|---|---|---|
| **STUDENT** | `student@ep4.edu` | `student123` | `/student/courses` |
| **TEACHER** | `vertiz@ep4.edu` | `teacher123` | `/teacher/dashboard` |
| **ADMIN** | `admin@ep4.edu` | `admin123` | `/admin/dashboard` |

---

## Modelo de Negocio Resumido

```
TEACHER crea DRAFT → pide aprobación →
ADMIN aprueba → curso aparece en CATALOGO →
STUDENT reserva asiento (5 min TTL) + vota horario + paga →
cuando 5 STUDENTS pagan → clase se INICIALIZA →
TEACHER cobra y puede descargar PDF de ingresos
```
