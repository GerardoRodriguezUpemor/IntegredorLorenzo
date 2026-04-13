# 📱 Flujo de Trabajo: Cliente (Estudiante)

Este documento detalla la experiencia del usuario final (Estudiante) en la plataforma MicroCohorts EP4.

## 1. Descubrimiento y Búsqueda
- **Catálogo Dinámico**: Acceso a todos los cursos aprobados por la administración.
- **Buscador en Tiempo Real**: Permite filtrar cursos instantáneamente por nombre o palabras clave.
- **Filtro de Disponibilidad**: Un interruptor para ocultar grupos que ya alcanzaron su capacidad máxima (5/5), permitiendo enfocarse en lo que está disponible.
- **Cálculo de "Próxima Clase"**: El sistema muestra la fecha más próxima o la fecha que va ganando en las votaciones directamente en la tarjeta del curso.

## 2. Proceso de Reserva (Checkout)
- **Precio Variable**: Implementación de decaimiento exponencial. Tu precio depende de cuántos alumnos se inscribieron antes que tú.
- **Votación de Horarios**: Al reservar, debes elegir una de las 3 opciones propuestas. Tu voto influye directamente en el horario final de la clase.
- **Reserva Temporal (TTL)**: El sistema bloquea tu lugar por **5 minutos**. Durante este tiempo el precio queda congelado. Si el tiempo expira, el lugar se libera automáticamente.

## 3. Gestión de Aprendizaje
- **Mis Cursos**: Panel centralizado para dar seguimiento a tus inscripciones pagadas y pendientes.
- **Horario Ganador**: Una vez que el grupo se llena (5/5), el sistema determina el horario oficial basado en la mayoría de votos.
- **Recibo de Pago (PDF)**: Descarga de un comprobante profesional que incluye:
    - Datos del curso y profesor.
    - Referencia de pago.
    - **Fecha y hora específica ganadora** de la votación.

## 4. Perfil y Personalización
- **Identidad Visual**: Avatar generado automáticamente con tus iniciales y un diseño premium.
- **Gestión de Contacto**: Registro de número telefónico para notificaciones importantes.
- **Seguridad**: Cambio de contraseña directo desde el panel de perfil.
