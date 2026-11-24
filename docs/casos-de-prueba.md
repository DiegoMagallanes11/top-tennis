# Casos de Prueba — Top Tennis

> Escenarios de prueba de los procesos **principal** (reservar y pagar una cancha) y **secundario** (mantenimiento de canchas), ejecutados con usuarios de distintos perfiles.
>
> Documentación relacionada: [Casos de uso](casos-de-uso.md) · [Reglas de negocio](reglas-de-negocio.md) · [Índice de código](../INDEX.md)

---

## Usuarios de prueba

| Usuario | Email | Contraseña | Perfil |
|---|---|---|---|
| Administrador | admin@toptennis.com | password | `admin` |
| Cliente Demo | cliente@toptennis.com | password | `cliente` |

> Datos generados por el seeder: `php artisan migrate:fresh --seed`

---

## 1. Proceso principal — Reservar y pagar una cancha

### CP-01 — Reserva con pago Yape (flujo feliz)

| Campo | Detalle |
|---|---|
| **Perfil** | Cliente |
| **Precondición** | Sesión iniciada como cliente; existe al menos un horario disponible a futuro |
| **Pasos** | 1. Ir a **Reservar** → elegir día, hora y cancha.<br>2. Pulsar "Reservar" sobre una cancha disponible.<br>3. Elegir método **Yape** e ingresar un N° de operación (ej. `12345678`).<br>4. Confirmar pago. |
| **Resultado esperado** | Redirige al **ticket digital** con código `TT-XXXX` y QR. La reserva queda `aprobado`. El horario desaparece del catálogo. |

### CP-02 — Reserva con pago en Efectivo y confirmación en mostrador

| Campo | Detalle |
|---|---|
| **Perfiles** | Cliente (reserva) + Administrador (confirma) — usar dos navegadores/ventanas |
| **Precondición** | Horario disponible con más de 30 minutos de anticipación |
| **Pasos** | 1. **[Cliente]** Reservar eligiendo método **Efectivo**.<br>2. Verificar que el ticket indica pago pendiente.<br>3. **[Admin]** Ir a **Reservas**, ubicar la reserva `pendiente`.<br>4. Pulsar **"Confirmar pago"**. |
| **Resultado esperado** | La reserva pasa de `pendiente` a `aprobado`; su monto se suma a "Ingresos confirmados" del dashboard y del historial. |

### CP-03 — N° de operación Yape duplicado (validación)

| Campo | Detalle |
|---|---|
| **Perfil** | Cliente |
| **Precondición** | Ya existe una reserva Yape con N° de operación `12345678` (CP-01) |
| **Pasos** | 1. Intentar una nueva reserva con Yape usando el **mismo** N° de operación. |
| **Resultado esperado** | Mensaje de validación: el número de operación ya fue utilizado. No se crea la reserva; no aparece pantalla de error de Laravel. |

### CP-04 — Reserva en Efectivo a menos de 30 minutos del juego

| Campo | Detalle |
|---|---|
| **Perfil** | Cliente |
| **Precondición** | Horario disponible que inicia en menos de 30 minutos |
| **Pasos** | 1. Intentar reservarlo con método **Efectivo**. |
| **Resultado esperado** | El sistema lo rechaza y exige pago con Yape (regla RN-01.5: el plazo de pago presencial ya no alcanza). |

### CP-05 — No-show: liberación automática de reserva vencida

| Campo | Detalle |
|---|---|
| **Perfiles** | Cliente (reserva) + Scheduler/lazy (anula) |
| **Precondición** | Reserva en Efectivo `pendiente` cuyo `expira_at` ya pasó |
| **Pasos** | 1. Esperar a que venza el plazo (o ajustar `expira_at` en BD para simular).<br>2. Cargar la página **Reservar** (dispara el modo lazy) o dejar correr el scheduler. |
| **Resultado esperado** | La reserva pasa a `anulada` y el horario vuelve a `disponible`; otro cliente puede tomarlo. |

### CP-06 — Doble reserva simultánea (anti-race)

| Campo | Detalle |
|---|---|
| **Perfiles** | Dos clientes distintos (o cliente + admin) en dos navegadores |
| **Precondición** | Ambos con el formulario de pago del **mismo** horario abierto |
| **Pasos** | 1. El usuario A confirma su pago.<br>2. Inmediatamente después, el usuario B confirma el suyo. |
| **Resultado esperado** | A obtiene su ticket. B recibe el mensaje "Ese horario acaba de ser reservado por otra persona" y ningún cobro se registra doble. |

### CP-07 — Acceso a ticket ajeno por URL (autorización por registro)

| Campo | Detalle |
|---|---|
| **Perfil** | Cliente |
| **Precondición** | Existe una reserva de otro usuario con ID conocido |
| **Pasos** | 1. Como cliente, navegar manualmente a `/reservas/{id}/ticket` de una reserva ajena. |
| **Resultado esperado** | Redirección con mensaje de acceso denegado; nunca se muestra el ticket de otro usuario. (El admin sí puede verlo.) |

### CP-08 — Cliente intenta entrar a las secciones de administración

| Campo | Detalle |
|---|---|
| **Perfil** | Cliente |
| **Pasos** | 1. Navegar manualmente a `/canchas`, `/tarifas` u `/horarios`. |
| **Resultado esperado** | Redirección al dashboard con el banner "Acceso denegado. Esa sección es exclusiva para el administrador del club." Los links de administración tampoco aparecen en su barra de navegación. |

---

## 2. Proceso secundario — Mantenimiento de canchas

### CP-10 — Poner una cancha en mantenimiento (con reembolso automático)

| Campo | Detalle |
|---|---|
| **Perfiles** | Administrador (ejecuta) + Cliente (afectado) |
| **Precondición** | Cancha `operativa` con al menos una reserva `aprobado` dentro del rango de mantenimiento |
| **Pasos** | 1. **[Admin]** En el dashboard, widget **"Estado de canchas"** → pulsar "Poner en mantenimiento".<br>2. Ingresar motivo y fecha/hora de fin (futura).<br>3. Confirmar. |
| **Resultado esperado** | La cancha pasa a `en_mantenimiento`. Las reservas aprobadas afectadas pasan a `cancelado_por_mantenimiento` y se registra el reembolso de cada una en el log. El mensaje de éxito indica cuántos reembolsos se procesaron. Los ingresos del dashboard se reducen. |

### CP-11 — El cliente ve la cancha fuera de servicio

| Campo | Detalle |
|---|---|
| **Perfil** | Cliente |
| **Precondición** | CP-10 ejecutado |
| **Pasos** | 1. Ir a **Reservar**.<br>2. Buscar la cancha en mantenimiento. |
| **Resultado esperado** | La cancha no aparece entre las reservables; se muestra en la sección "Temporalmente no disponibles" con el motivo y la fecha de reapertura. En **Mis reservas**, la reserva afectada figura como cancelada por mantenimiento. |

### CP-12 — Fecha de fin en el pasado (validación)

| Campo | Detalle |
|---|---|
| **Perfil** | Administrador |
| **Pasos** | 1. Abrir el modal de mantenimiento e intentar guardar con una fecha de fin anterior a ahora. |
| **Resultado esperado** | Error de validación "La fecha de fin debe ser posterior a ahora"; la cancha no cambia de estado. |

### CP-13 — Restauración manual

| Campo | Detalle |
|---|---|
| **Perfil** | Administrador |
| **Precondición** | Cancha `en_mantenimiento` |
| **Pasos** | 1. En el widget "Estado de canchas", pulsar **"Restaurar"** y confirmar. |
| **Resultado esperado** | La cancha vuelve a `operativa` y reaparece en el catálogo reservable del cliente. |

### CP-14 — Restauración automática al vencer el plazo

| Campo | Detalle |
|---|---|
| **Perfil** | Ninguno (proceso automático) |
| **Precondición** | Cancha `en_mantenimiento` con `fin_mantenimiento` ya vencido (simular ajustando la fecha en BD) |
| **Pasos** | 1. Cargar el dashboard del admin o el catálogo del cliente (modo lazy). |
| **Resultado esperado** | `Cancha::restaurarVencidas()` la devuelve a `operativa` sin intervención humana. |

---

## 3. CRUD — pruebas de integridad

### CP-20 — Eliminar una cancha con horarios asociados

| Campo | Detalle |
|---|---|
| **Perfil** | Administrador |
| **Pasos** | 1. En **Canchas**, intentar eliminar una cancha que tiene horarios. |
| **Resultado esperado** | Mensaje "No se puede eliminar: tiene horarios asociados". No hay error de integridad ni pantalla de excepción. |

### CP-21 — Eliminar una tarifa en uso

| Campo | Detalle |
|---|---|
| **Perfil** | Administrador |
| **Pasos** | 1. En **Tarifas**, intentar eliminar una tarifa asignada a horarios. |
| **Resultado esperado** | Mensaje de rechazo equivalente a CP-20; la tarifa se conserva. |

### CP-22 — Editar/eliminar un horario reservado

| Campo | Detalle |
|---|---|
| **Perfil** | Administrador |
| **Pasos** | 1. En **Horarios**, intentar editar o eliminar un slot en estado `reservado`. |
| **Resultado esperado** | La acción se bloquea con mensaje: primero debe cancelarse la reserva. |

---

## Matriz resumen

| Caso | Proceso | Perfiles involucrados | Regla que verifica |
|---|---|---|---|
| CP-01 | Principal | Cliente | Flujo feliz Yape (RN-01.1) |
| CP-02 | Principal | Cliente + Admin | Pago presencial (RN-01.3, RN-01.6) |
| CP-03 | Principal | Cliente | Comprobante único (RN-01.2) |
| CP-04 | Principal | Cliente | Plazo de 30 min (RN-01.5) |
| CP-05 | Principal | Scheduler | No-shows (RN-04.1, RN-04.2) |
| CP-06 | Principal | Cliente × 2 | Anti-doble reserva (RN-03.2) |
| CP-07 | Principal | Cliente | Autorización por registro (RN-07.4) |
| CP-08 | Principal | Cliente | Control de acceso por rol (RN-07.2, RN-07.3) |
| CP-10 | Secundario | Admin + Cliente | Mantenimiento + reembolso (RN-05.1, RN-05.2) |
| CP-11 | Secundario | Cliente | Cancha fuera del catálogo (RN-05.3) |
| CP-12 | Secundario | Admin | Validación de fechas (RN-05.1) |
| CP-13 | Secundario | Admin | Restauración manual (RN-05.4) |
| CP-14 | Secundario | Automático | Restauración automática (RN-05.4) |
| CP-20–22 | CRUD | Admin | Integridad referencial (RN-06.1, RN-06.2, RN-06.3) |
