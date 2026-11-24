# Top Tennis: informe funcional de la aplicación

**Fecha de revisión:** 11 de octubre de 2026.

**Actualización operativa:** 13 de octubre de 2026.

**Actualización de pagos:** online sin Efectivo, con simulador local de Yape/tarjetas; [detalle de demostración](pagos-demostracion.md).

**Fuente:** código del repositorio, no datos de clientes ni supuestos sobre servicios externos.

## Alcance y criterio de revisión

Se revisaron rutas, controladores, servicios, modelos, permisos, formularios, vistas, configuración, migraciones, comandos y pruebas. Este informe describe el comportamiento implementado; no certifica el funcionamiento de un servidor de producción.

El prompt original se precisó con cuatro criterios: dar prioridad al código ejecutable frente a comentarios antiguos; distinguir implementación de configuración y entrega externa; identificar valores predeterminados frente a reglas guardadas; explicar los límites de cada función. Esta entrega solo documenta: no cambia reservas, usuarios ni configuración de MySQL.

## 1. Qué es y para qué sirve

Top Tennis permite consultar canchas, solicitar un horario, obtener un ticket y gestionar la operación del club. El cliente usa su cuenta para reservar. Recepción verifica cobros y registra ingresos. Administración configura el club y consulta su actividad.

La aplicación utiliza Laravel y Blade para procesar y mostrar información, Tailwind CSS v3 para los estilos y Alpine.js para selectores y formularios. FullCalendar muestra la agenda; Chart.js dibuja métricas; BaconQrCode genera QR y DomPDF genera tickets PDF. Las dependencias están declaradas en [composer.json](../composer.json) y [package.json](../package.json).

### Página pública

- La home presenta el club, beneficios, pasos para reservar, canchas y contacto.
- Consulta hasta seis canchas operativas de la base; las tarjetas muestran sus imágenes y características.
- Los botones de reserva de visitantes conducen al registro. Con sesión iniciada conducen a disponibilidad.
- La página pública de canchas permite consultar las operativas sin iniciar sesión.
- El contacto incluye dirección, teléfono y enlaces externos. Un enlace a WhatsApp o Instagram no significa que el sistema envíe mensajes automáticamente.
- La home obtiene el horario de atención de las reglas del club. No presenta reseñas inventadas: la sección de prueba social está omitida expresamente.

Fuentes: [routes/web.php](../routes/web.php), [welcome.blade.php](../resources/views/welcome.blade.php), [CanchaPublicoController.php](../app/Http/Controllers/CanchaPublicoController.php).

## 2. Roles y permisos

“Staff” significa administrador o recepcionista. “Invitado” significa persona sin sesión; no es un rol guardado en la tabla de usuarios.

| Acción | Invitado | Cliente | Recepcionista | Administrador |
|---|---|---|---|---|
| Ver home y canchas públicas | Sí | Sí | Sí | Sí |
| Consultar estado de un QR mediante su enlace | Sí | Sí | Sí | Sí |
| Registrarse como cliente | Sí | Ya tiene cuenta | Ya tiene cuenta | Ya tiene cuenta |
| Panel y perfil propio | No | Sí | Sí | Sí |
| Solicitar reserva para sí mismo | No | Sí | Usa reserva manual para un cliente | Usa reserva manual para un cliente |
| Ver reservas y tickets | No | Solo propios | Todos | Todos |
| Cambiar o cancelar reservas activas | No | Propias, dentro del plazo | Sí, sin plazo de cliente | Sí, sin plazo de cliente |
| Confirmar pago, rechazar o completar | No | No | Sí | Sí |
| Reserva manual y registro de ingreso | No | No | Sí | Sí |
| Calendario operativo | No | No | Sí | Sí |
| Gestionar usuarios, canchas, tarifas y horarios | No | No | No | Sí |
| Mantenimiento/restauración de canchas | No | No | No | Sí |
| Reglas y auditoría general | No | No | No | Sí |

Las rutas de disponibilidad son compartidas por usuarios autenticados, pero el servicio impide crear una reserva de cliente usando una cuenta del personal como titular. Los permisos se comprueban en el servidor; ocultar un botón no es la única protección.

Fuentes: [Rol.php](../app/Enums/Rol.php), [RoleMiddleware.php](../app/Http/Middleware/RoleMiddleware.php), [ReservaPolicy.php](../app/Policies/ReservaPolicy.php), [GestorReservas.php](../app/Services/GestorReservas.php).

## 3. Mapa de rutas principales

GET consulta o abre una página; POST crea o ejecuta una acción; PATCH/PUT actualiza; DELETE solicita eliminación o cancelación. `{horario}`, `{reserva}` y otros parámetros identifican registros.

### Públicas y de acceso a la cuenta

| Método | Ruta | Función |
|---|---|---|
| GET | `/` | Home; nombre de ruta `welcome` |
| GET | `/club/canchas` | Canchas públicas |
| GET | `/validar/{token}` | Estado del ticket por QR |
| GET | `/up` | Comprobación básica de salud de Laravel |
| GET, POST | `/register` | Registro; disponible sin sesión |
| GET, POST | `/login` | Inicio de sesión; disponible sin sesión |
| GET, POST | `/forgot-password` | Solicitud de recuperación |
| GET | `/reset-password/{token}` | Formulario de nueva contraseña |
| POST | `/reset-password` | Guardar contraseña mediante token |

### Autenticadas

| Método | Ruta | Función y límite |
|---|---|---|
| GET | `/dashboard` | Panel según el rol; declara `auth` y `verified` |
| GET, PATCH, DELETE | `/profile` | Consultar/editar perfil o solicitar baja propia |
| PUT | `/password` | Cambiar contraseña propia |
| GET, POST | `/confirm-password` | Confirmar contraseña |
| POST | `/logout` | Cerrar sesión |
| GET | `/verify-email` | Ruta de verificación de correo del módulo de acceso |
| GET | `/verify-email/{id}/{hash}` | Verificación con enlace firmado |
| POST | `/email/verification-notification` | Solicitar aviso de verificación |
| GET | `/reservar` | Disponibilidad; `reservas.disponibles` |
| GET | `/reservar/{horario}/confirmar` | Revisar solicitud antes de enviarla |
| POST | `/reservas` | Crear reserva de cliente |
| GET | `/reservas` | Historial propio o general según el rol |
| GET | `/reservas/{reserva}/ticket` | Ticket; propietario o staff |
| GET | `/reservas/{reserva}/ticket/pdf` | Descargar PDF; propietario o staff |
| GET, POST | `/reservas/{reserva}/pago-demo` | Simular resultado; propietario cliente y demostración habilitada |
| GET | `/reservas/{reserva}/edit` | Formulario de cambio; propietario o staff |
| PATCH | `/reservas/{reserva}` | Cambiar horario respetando reglas |
| DELETE | `/reservas/{reserva}` | Cancelar; conserva el registro |

**Verificación de correo:** existen estas rutas y el panel declara `verified`, pero `User` no implementa el contrato `MustVerifyEmail`. Por ello no se debe afirmar que verificar el correo sea obligatorio ni que el registro envíe automáticamente un correo de verificación.

### Staff: administrador y recepcionista

| Método | Ruta | Función |
|---|---|---|
| GET | `/calendario` | Calendario operativo |
| GET | `/calendario/datos` | Datos del calendario en JSON |
| GET | `/reservas/crear-manual` | Formulario de reserva manual |
| POST | `/reservas/manual` | Registrar reserva con cobro declarado en caja |
| PATCH | `/reservas/{reserva}/confirmar-pago` | Aprobar pago manualmente |
| PATCH | `/reservas/{reserva}/estado` | Rechazar o completar reserva |
| POST | `/validar/{token}/usar` | Registrar ingreso por ticket |

### Solo administrador

| Métodos y rutas | Función |
|---|---|
| GET `/club/reglas`; PATCH `/club/reglas` | Consultar y guardar reglas |
| GET `/club/auditoria` | Acciones y estados de avisos |
| GET `/usuarios`, `/usuarios/create`, `/usuarios/{usuario}/edit`; POST `/usuarios`; PATCH/DELETE `/usuarios/{usuario}` | Gestión de usuarios |
| PATCH `/usuarios/{usuario}/activo` | Activar/desactivar cuenta |
| GET `/canchas`, `/canchas/create`, `/canchas/{cancha}/edit`; POST `/canchas`; PATCH/DELETE `/canchas/{cancha}` | Gestión de canchas |
| POST `/canchas/{cancha}/mantenimiento`, `/canchas/{cancha}/restaurar` | Mantenimiento y restauración |
| GET `/tarifas`, `/tarifas/create`, `/tarifas/{tarifa}/edit`; POST `/tarifas`; PATCH/DELETE `/tarifas/{tarifa}` | Gestión de tarifas |
| GET `/horarios`, `/horarios/create`, `/horarios/{horario}/edit`; POST `/horarios`; PATCH/DELETE `/horarios/{horario}` | Gestión de horarios |
| DELETE `/horarios-dia` | Eliminar horarios disponibles de una fecha, opcionalmente por cancha |
| POST `/horarios/cambiar-tarifa` | Cambiar tarifa de varios horarios disponibles |

Fuentes del mapa: [routes/web.php](../routes/web.php), [routes/auth.php](../routes/auth.php), [bootstrap/app.php](../bootstrap/app.php).

## 4. Funcionalidades por rol

### Invitado

- Consulta presentación, contacto y canchas operativas.
- Crea una cuenta de cliente o inicia sesión.
- Solicita recuperación de contraseña.
- Consulta si un ticket es válido al abrir su enlace QR; no puede registrar el ingreso.

### Cliente

- Elige día, hora y cancha entre los cupos reservables.
- Revisa duración, importe, método de pago y plazos antes de solicitar.
- Ve su próximo partido, estado del pago y enlace al ticket.
- El panel cuenta únicamente los cupos libres y futuros de hoy; el selector de reserva permite consultar otras fechas, un día a la vez. Cada cancha disponible cuenta como un cupo, aunque comparta hora con otra.
- Consulta y filtra su historial; abre tickets y descarga PDF.
- Cambia o cancela reservas propias cuando las reglas lo permiten.
- Cambia su nombre, ícono de perfil y contraseña. El formulario de perfil no permite editar correo ni teléfono.
- Solicita baja de su cuenta con contraseña actual, siempre que no tenga reservas pendientes/confirmadas cuyo horario aún no terminó.

### Recepcionista

- Consulta reservas de todos los clientes y filtros por fecha, cancha, cliente, estado, método de pago o búsqueda.
- Ve agenda de hoy y pagos pendientes ordenados por vencimiento.
- Abre tickets, comprueba pagos y registra ingresos por QR.
- Crea reservas manuales para clientes existentes o nuevos.
- Cambia, cancela, rechaza o completa reservas según las condiciones del servicio.
- Consulta calendario y métricas del panel compartido con administración.
- No administra usuarios, tarifas, horarios, mantenimiento ni reglas.

### Administrador

- Dispone de las funciones de recepción.
- Crea/edita usuarios, asigna uno de los tres roles y activa/desactiva cuentas.
- Elimina usuarios mediante baja lógica: los registros siguen en la base para conservar relaciones.
- No puede eliminarse, desactivarse ni quitarse su propio rol desde la gestión de usuarios. Las cuentas del personal tampoco pueden darse de baja desde su perfil.
- Gestiona características de canchas, precios, horarios, mantenimiento y reglas.
- Consulta registro de acciones, sus detalles disponibles y estado de avisos.

Fuentes: [DashboardController.php](../app/Http/Controllers/DashboardController.php), [UserController.php](../app/Http/Controllers/UserController.php), [ProfileController.php](../app/Http/Controllers/ProfileController.php), [ProfileUpdateRequest.php](../app/Http/Requests/ProfileUpdateRequest.php).

## 5. Flujos principales

### 5.1. Registro, inicio de sesión y recuperación

1. El visitante introduce nombre, correo y contraseña con confirmación. El correo debe ser válido, estar en minúsculas y no existir previamente.
2. El sistema crea una cuenta de cliente; el formulario público no permite elegir administrador o recepcionista.
3. Inicia la sesión, renueva su identificador y dirige al panel.
4. En accesos posteriores valida correo y contraseña. Una cuenta desactivada no puede continuar.
5. Tras iniciar sesión vuelve a la página protegida solicitada previamente, si existe; de lo contrario abre el panel.
6. Para recuperar acceso, el usuario solicita un enlace y establece una contraseña nueva mediante un token. El plazo configurado del token es 60 minutos.
7. La respuesta de recuperación no revela si existe la cuenta. En transporte local informa que no se envía correo externo.

Fuentes: [RegisteredUserController.php](../app/Http/Controllers/Auth/RegisteredUserController.php), [AuthenticatedSessionController.php](../app/Http/Controllers/Auth/AuthenticatedSessionController.php), [PasswordResetLinkController.php](../app/Http/Controllers/Auth/PasswordResetLinkController.php), [config/auth.php](../config/auth.php).

### 5.2. Disponibilidad → solicitud → ticket

1. El cliente abre `/reservar`. El servidor restaura mantenimientos vencidos, libera reservas pendientes cuyo pago venció y completa los horarios automáticos de las próximas fechas según la configuración.
2. Obtiene horarios libres y futuros, de canchas operativas con tarifa, y aplica reglas de atención, anticipación, festivos, descanso y margen entre reservas.
3. Para solicitudes con pago pendiente exige más de 30 minutos antes del inicio, tanto para Efectivo como para Yape.
4. La vista selecciona hoy si hay cupos; si no, selecciona la primera fecha posterior con cupo. Si no hay ninguno, muestra el estado sin horarios.
5. El cliente elige día, hora y cancha. Se muestra el importe de la tarifa y la duración real del horario.
6. En demostración elige Yape o tarjeta, sin número de operación ni datos bancarios. Efectivo queda solo en recepción. Fuera de demostración, Yape requiere habilitación y número de operación.
7. Al enviar, el servidor vuelve a comprobar disponibilidad y reglas. Bloquea los registros durante la operación y cambia el horario de libre a reservado de forma condicional.
8. Guarda la reserva pendiente, el precio de ese momento, el contacto, un código único y el vencimiento del pago: inicio menos 30 minutos.
9. En demostración continúa al simulador de aprobación/rechazo. La aprobación produce un ticket confirmado de prueba, sin cobro real ni autorización de ingreso. En Yape manual abre el ticket pendiente hasta su verificación por recepción. Ambos admiten PDF.

La vista actualiza su reloj cada 30 segundos, pero no descarga nuevas reservas de otros usuarios en cada actualización. La disponibilidad cargada puede cambiar; la comprobación definitiva ocurre en el servidor al solicitar.

Fuentes: [ReservaController.php](../app/Http/Controllers/ReservaController.php), [disponibles.blade.php](../resources/views/reservas/disponibles.blade.php), [confirmar.blade.php](../resources/views/reservas/confirmar.blade.php), [ReglasReserva.php](../app/Services/ReglasReserva.php), [GestorReservas.php](../app/Services/GestorReservas.php).

### 5.3. Verificación del pago e ingreso por QR

1. El cliente realiza o entrega el pago por el medio elegido, fuera de una pasarela integrada en esta aplicación.
2. Recepción o administración comprueba por su cuenta que el club recibió el dinero.
3. Usa la acción de confirmar pago. El sistema exige una reserva activa, pendiente y sin vencimiento.
4. Cambia pago a `aprobado`, reserva a `confirmada` y elimina el vencimiento pendiente.
5. Al escanear el QR se abre una URL pública con token aleatorio de 40 caracteres. Se consulta el estado actual, no una autorización fija dentro de la imagen.
6. Un ticket cancelado, rechazado, completado, vencido, pendiente de pago o ya utilizado no permite registrar ingreso. Tampoco uno cuyo horario terminó.
7. El personal autenticado registra el ingreso. El servidor vuelve a comprobar el ticket dentro de una transacción y guarda `usado_at`.
8. Un segundo intento indica que el ticket ya fue utilizado.

**Límites actuales:** no hay una ventana mínima de llegada: un ticket aprobado de una fecha futura puede permitir registrar ingreso antes del día del partido. La página pública oculta los datos personales al visitante, pero quien posee el enlace puede consultar el estado del ticket. Registrar ingreso no cambia automáticamente la reserva a completada.

Fuentes: [Reserva.php](../app/Models/Reserva.php), [ValidacionController.php](../app/Http/Controllers/ValidacionController.php), [validacion/mostrar.blade.php](../resources/views/validacion/mostrar.blade.php).

Los pasos de ingreso descritos corresponden a reservas reales. Los tickets con `pago_demo=true` siempre rechazan ingreso; recepción tampoco puede aprobar sus pagos como cobros reales.

### 5.4. Cancelación y modificación

1. El cliente abre una reserva propia; staff puede gestionar la de cualquier cliente.
2. El servidor exige que siga activa y que el pago pendiente no haya vencido.
3. Para el cliente, el límite es inicio menos las horas de cancelación configuradas; al llegar a ese momento ya no puede cambiar ni cancelar. También se bloquea si ya registró ingreso.
4. Al cancelar libera el horario, conserva la reserva con estado `cancelada`, guarda motivo y registra la acción.
5. Para cambiar elige otro horario libre que cumpla las reglas. Si el pago estaba aprobado, el precio nuevo debe ser igual al guardado.
6. Con pago pendiente, el nuevo horario debe dejar más de 30 minutos para verificarlo; se actualizan importe y vencimiento.
7. Libera el horario anterior, ocupa el nuevo y conserva la identidad y código del ticket.

Staff no tiene el límite de anticipación ni la restricción de ticket usado del cliente; sí conserva las restricciones de reserva activa, vencimiento, disponibilidad y precio de un pago aprobado. Cancelar o rechazar conserva el estado del pago: no devuelve dinero automáticamente.

Fuentes: [GestorReservas.php](../app/Services/GestorReservas.php), [ReservaPolicy.php](../app/Policies/ReservaPolicy.php).

### 5.5. Reserva manual del staff

1. Recepción o administración abre el formulario manual y selecciona un horario futuro reservable.
2. Elige un cliente activo existente o registra nombre y teléfono de uno nuevo.
3. Para uno nuevo crea una cuenta con correo interno aleatorio `@toptennis.local` y contraseña aleatoria. No recoge su correo real ni le entrega credenciales mediante este flujo.
4. Indica Efectivo o Yape; el número de operación es opcional en este formulario.
5. Al guardar registra el cobro declarado en caja, crea reserva confirmada y pago aprobado, sin vencimiento pendiente.
6. Guarda el precio y el evento `creada_en_caja`.

Este flujo supone que el personal ya cobró; no comprueba bancos. Puede reservar con menos de 30 minutos si las demás reglas lo permiten. No envía avisos a cuentas con correo interno `@toptennis.local`.

Fuente: [ReservaController.php](../app/Http/Controllers/ReservaController.php).

### 5.6. Mantenimiento y restauración

1. Administración indica motivo y fecha/hora futura de término.
2. El sistema localiza reservas pendientes o confirmadas de la cancha cuyo final es posterior a ahora y cuyo inicio no supera el término del mantenimiento.
3. Cancela las afectadas, conserva su estado de pago, libera sus horarios y registra eventos de mantenimiento.
4. Marca la cancha `en_mantenimiento`; deja de ofrecer sus horarios para nuevas reservas mientras mantenga ese estado.
5. Administración puede restaurarla manualmente. También se restaura al vencer el mantenimiento mediante consultas operativas o el comando de vencimientos.
6. Restaurar no revive las reservas canceladas ni genera automáticamente nuevos horarios.

Los saldos pagados deben resolverse en caja. Existen comentarios antiguos que hablan de reembolsos, pero el código actual no ejecuta una devolución.

Fuentes: [CanchaController.php](../app/Http/Controllers/CanchaController.php), [Cancha.php](../app/Models/Cancha.php), [LiberarReservasVencidas.php](../app/Console/Commands/LiberarReservasVencidas.php).

### 5.7. Canchas, tarifas y horarios

- **Canchas:** crear/editar nombre, superficie, imagen y características admitidas. La eliminación es lógica y se bloquea si hay horarios asociados. Aunque el club es de arcilla, el modelo todavía admite otras superficies: la exclusividad de arcilla no es una restricción absoluta del código.
- **Tarifas:** crear/editar nombre y precio. El precio se vincula a horarios; una reserva conserva el precio tomado al crearla. Eliminar una tarifa se bloquea si tiene horarios asociados.
- **Generación de horarios:** elegir fechas, canchas, rango de horas y tarifas de día/noche. Recorre cada fecha y cancha usando la duración configurada; antes de las 18:00 aplica tarifa de día, desde las 18:00 la nocturna. Omite cruces, horarios no permitidos y franjas sin tarifa.
- **Edición de horarios:** comprueba fechas, cancha, tarifa, cruces y reglas. No permite editar horarios reservados ni cambiar su estado manualmente desde ese formulario.
- **Eliminación:** los horarios reservados están protegidos; la eliminación diaria afecta solo a disponibles.
- **Tarifa masiva:** cambia la tarifa de los horarios seleccionados que siguen disponibles; no modifica los reservados.
- **Publicación automática:** completa las fechas futuras al abrir reservas, panel o datos del calendario. Por defecto mantiene 30 días calendario, incluyendo hoy, limitados por la anticipación máxima. Usa las tarifas de día/noche elegidas en Reglas del club; sin selección busca una única tarifa con nombre `Tarifa Día` y otra `Tarifa Noche`. Si falta alguna o hay nombres ambiguos, avisa al personal y no inventa precios. No modifica horarios existentes ni vuelve a crear franjas retiradas mediante baja lógica.

Fuentes: [CanchaController.php](../app/Http/Controllers/CanchaController.php), [TarifaController.php](../app/Http/Controllers/TarifaController.php), [HorarioController.php](../app/Http/Controllers/HorarioController.php), [UpdateHorarioRequest.php](../app/Http/Requests/UpdateHorarioRequest.php), [PublicadorHorarios.php](../app/Services/PublicadorHorarios.php).

### 5.8. Reglas del club y auditoría

Administración configura apertura/cierre por día, días activos, fechas no laborables, descanso, anticipación mínima y máxima, horas límite para cambios/cancelación, margen entre reservas y duración de generación.

| Regla | Valor predeterminado en el código |
|---|---|
| Atención lunes a viernes | 06:00–22:00 |
| Sábado / domingo | 06:00–20:00 / 06:00–15:00 |
| Días activos | Todos |
| Festivos / descanso | Sin fechas bloqueadas ni descanso |
| Anticipación mínima / máxima | 0 minutos / 90 días |
| Cambios y cancelación del cliente | Hasta antes de 2 horas del inicio |
| Margen entre reservas | 0 minutos |
| Duración para generar horarios | 60 minutos |
| Publicación automática / fechas por adelantado | Activada / 30 días, contando hoy |
| Tarifas automáticas | Selección del administrador; sin selección, busca `Tarifa Día` y `Tarifa Noche` |
| Vencimiento del pago pendiente | 30 minutos antes del inicio; constante del modelo, fuera del formulario de reglas |

Los valores guardados pueden ser distintos. Configuraciones antiguas sin horarios semanales conservan su apertura/cierre uniforme hasta actualizarse. Cambiar reglas no cancela reservas existentes ni transforma horarios guardados: la publicación automática solo agrega franjas que faltan, respetando las reglas vigentes. La duración controla la generación; no transforma retrospectivamente todos los registros a esa duración. Administración puede desactivar la publicación automática para gestionar fechas manualmente.

El registro de acciones muestra actor, fecha, acción, reserva asociada y detalle disponible. Los eventos específicos de reservas guardan algunos datos anteriores/posteriores; las acciones administrativas generales guardan ruta y método. No es una captura completa de cada campo antes/después de todo cambio. La restauración automática de mantenimiento hace una actualización masiva sin crear un evento específico por cancha.

La auditoría también permite consultar avisos y sus estados. Es distinta del historial del desarrollo: [CHANGELOG.md](../CHANGELOG.md) y Git describen versiones del proyecto, no operaciones de clientes. No existe historial automático retrospectivo de acciones realizadas antes de implementar el registro.

Fuentes: [ClubSetting.php](../app/Models/ClubSetting.php), [ClubController.php](../app/Http/Controllers/ClubController.php), [RegistrarAdministracion.php](../app/Http/Middleware/RegistrarAdministracion.php), [RegistroReserva.php](../app/Services/RegistroReserva.php).

## 6. Modelos de datos y relaciones

| Modelo / tabla | Qué guarda | Relación principal |
|---|---|---|
| `User` / `users` | Nombre, correo, teléfono, contraseña protegida, rol, activo, ícono | Un usuario tiene muchas reservas |
| `Cancha` / `canchas` | Nombre, superficie, imagen, modalidad, iluminación, mantenimiento | Una cancha tiene muchos horarios |
| `Tarifa` / `tarifas` | Nombre y precio | Una tarifa se aplica a muchos horarios |
| `Horario` / `horarios` | Cancha, tarifa, inicio, fin, estado | Pertenece a una cancha y una tarifa |
| `Reserva` / `reservas` | Cliente, horario, método, operación, estados, importe, contacto, código, token, vencimiento e ingreso | Pertenece a usuario y horario; tiene eventos |
| `ClubSetting` / `club_settings` | Reglas en un bloque JSON | El código usa el registro con ID 1 |
| `BookingEvent` / `booking_events` | Actor, acción, datos y fecha | Puede vincularse a una reserva; actor puede faltar |
| `BookingNotification` / `booking_notifications` | Destinatario, evento, contenido, estado, intentos y error | La base vincula el aviso a una reserva |

Reserva también guarda `pago_demo` para identificar las pruebas. Tarjeta es un método admitido por el esquema y utilizado únicamente por el simulador actual.

Un horario puede tener varias reservas **históricas**, por ejemplo después de una cancelación y nueva solicitud. La relación `Horario::reserva()` devuelve la última pendiente/confirmada, no todas las anteriores. Una migración eliminó la restricción antigua de una única reserva histórica por horario; la ocupación actual se controla en el servicio.

Usuarios, canchas, tarifas, horarios y reservas admiten baja lógica mediante `deleted_at`. Eventos, avisos y reglas no usan ese mecanismo. Las relaciones de reserva con usuario y horario incluyen registros dados de baja para conservar consultas históricas; otras relaciones no incluyen automáticamente todos los registros eliminados.

También existen tablas técnicas de sesiones, recuperación de contraseñas, caché y trabajos. No constituyen módulos comerciales visibles para el cliente.

Fuentes: [app/Models](../app/Models), [database/migrations](../database/migrations), [migración del flujo de reservas](../database/migrations/2026_10_11_000000_add_booking_workflow.php).

## 7. Estados de reserva, pago e ingreso

### Reserva

| Estado | Significado |
|---|---|
| `pendiente` | Horario solicitado y ocupado; falta verificar pago |
| `confirmada` | Pago aprobado por staff o cobro manual registrado |
| `cancelada` | Cancelación solicitada, vencimiento o mantenimiento |
| `rechazada` | Staff rechaza la reserva y registra motivo |
| `completada` | Staff la completa cuando está pagada y terminó el horario |

No hay un cambio automático a completada solo porque termina el partido. Rechazar exige motivo; completar requiere pago aprobado y horario finalizado.

### Pago

| Estado | Significado |
|---|---|
| `pendiente` | Falta comprobación del personal |
| `aprobado` | Staff registró la confirmación del cobro |
| `anulada` | Venció el plazo de pago pendiente |
| `cancelado_por_mantenimiento` | Valor conservado por el esquema/modelo para datos anteriores; el flujo actual de mantenimiento conserva el pago y cancela la reserva |

Una reserva cancelada puede conservar pago aprobado. Eso representa un cobro registrado cuya devolución aún puede necesitar gestión; no una reserva válida ni un reembolso realizado. `monto_pagado` guarda el importe de la reserva incluso si el pago sigue pendiente: su nombre no demuestra que se cobró.

El ingreso se registra aparte en `usado_at`; no es otro estado de pago. El horario tiene `disponible` o `reservado` y la cancha `operativa` o `en_mantenimiento`.

Fuentes: [Reserva.php](../app/Models/Reserva.php), [GestorReservas.php](../app/Services/GestorReservas.php), [CanchaController.php](../app/Http/Controllers/CanchaController.php).

## 8. Correos y notificaciones

### Avisos de reservas

El servicio registra avisos para creación, creación en caja, cambio, confirmación, cancelación, rechazo, finalización, vencimiento y cancelación por mantenimiento. El comando genera un recordatorio para reservas confirmadas próximas a comenzar; por defecto dentro de 24 horas, una sola vez por reserva.

| Estado del aviso | Qué significa |
|---|---|
| `sin_configurar` | Se registró con los avisos desactivados; no se enviará automáticamente al activar correo después |
| `pendiente` | Espera procesamiento |
| `enviando` | Un proceso tomó el aviso y aumentó el contador de intentos |
| `enviado` | El transporte de correo aceptó el envío; no prueba lectura ni llegada a la bandeja principal |
| `error` | Falló el intento; admite reintentos hasta tres en total |

El comando procesa hasta 50 avisos por ejecución. No intenta envío externo con transporte `log`/`array` o avisos desactivados. Los destinatarios internos `@toptennis.local` se omiten. Un aviso que quede `enviando` tras interrumpir el proceso no se recupera automáticamente con la selección actual.

### Recuperación y verificación de cuenta

La recuperación utiliza el servicio de contraseñas de Laravel y el transporte de correo configurado. Es independiente de `MAIL_NOTIFICATIONS_ENABLED`, que controla los avisos de reservas. Las rutas de verificación existen, pero el modelo actual no exige esa verificación para operar.

### Configuración y automatización

`config/mail.php` y `.env.example` dejan por defecto transporte `log` y avisos desactivados. Hay configuración para SMTP y otros transportes; su presencia no prueba que exista un proveedor activo ni entrega real. No se verificaron credenciales ni se enviaron correos externos para este informe.

El código programa cada minuto la liberación de vencimientos y el envío de avisos. Necesita que el servidor ejecute el planificador de Laravel; declarar la programación no demuestra que esté corriendo. El vencimiento tiene además comprobaciones al abrir páginas operativas; los correos requieren ejecutar su comando.

La publicación de horarios está programada a las 00:05 en la zona horaria configurada. También se ejecuta al abrir páginas operativas, por lo que las fechas se renuevan sin depender exclusivamente del planificador. La comprobación se guarda hasta el fin del día; cambios en reglas, tarifas o canchas hacen que se revise nuevamente. Las inserciones se protegen con transacciones y bloqueo por cancha.

No hay notificaciones automáticas de WhatsApp, SMS ni notificaciones push implementadas en este recorrido.

Fuentes: [RegistroReserva.php](../app/Services/RegistroReserva.php), [EnviarAvisosReservas.php](../app/Console/Commands/EnviarAvisosReservas.php), [reserva-evento.blade.php](../resources/views/emails/reserva-evento.blade.php), [config/mail.php](../config/mail.php), [routes/console.php](../routes/console.php), [.env.example](../.env.example).

Las reservas de demostración no generan correos ni recordatorios; conservan sus eventos para revisión.

## 9. Seguridad y validaciones

- **Sesión y contraseñas:** contraseñas almacenadas con hash; renovación de sesión al acceder/registrarse e invalidación al salir. No se guardan como texto visible.
- **Cuentas activas:** login y middleware bloquean las desactivadas, incluso si ya tenían sesión.
- **Roles y propiedad:** middleware restringe módulos; políticas restringen reservas/tickets al propietario o al personal autorizado. El cliente no elige su rol al registrarse.
- **Formularios:** protección CSRF y validaciones de fechas, importes, identificadores, teléfono y métodos permitidos. La contraseña actual se exige para cambiar contraseña y solicitar baja propia.
- **Contraseñas nuevas:** mínimo de ocho caracteres fuera de producción; en producción el valor predeterminado exige doce, mayúsculas/minúsculas, números y símbolos. El comando de creación de administrador tiene su propia validación.
- **Reservas simultáneas:** transacciones, bloqueo de registros y actualización condicional del horario. La clave UUID permite reconocer reintentos de una solicitud sin crear otra reserva cuando coincide.
- **Referencias de pago:** número de operación único y validado; evita reutilizar una referencia guardada, pero no verifica su autenticidad bancaria.
- **Reglas:** se vuelven a comprobar en el servidor; la selección del navegador no basta para reservar.
- **QR:** token aleatorio; página con indicaciones para no indexar, no cachear y no enviar referencia a otros sitios. Registrar ingreso exige staff y se protege con bloqueo de la reserva.
- **Esquema pendiente:** si falta la columna del nuevo flujo, las operaciones autenticadas correspondientes muestran indisponibilidad temporal en lugar de continuar sobre una base sin actualizar. No es una comprobación de todo el esquema.
- **Errores:** hay páginas y mensajes para recursos inexistentes, permisos, sesión vencida y errores de base; se registran detalles técnicos en logs.

### Límites de peticiones implementados

| Operación | Límite declarado |
|---|---|
| Fallos de login | Cinco por combinación correo/IP antes del bloqueo temporal |
| Registro | Seis peticiones por minuto |
| Solicitud/restablecimiento de contraseña | Cinco por minuto en cada ruta POST |
| Verificación/reenvío de correo | Seis por minuto |
| Crear reserva de cliente/manual | Diez por minuto en cada ruta POST |
| Consulta de calendario | Intervalo máximo de 93 días por solicitud; es límite de rango, no frecuencia |

No hay un límite específico declarado para todas las rutas, incluido el GET público del QR. No se identifica autenticación de dos factores en el código revisado. La configuración de HTTPS, cookies y acceso a base necesita comprobarse en el despliegue; este informe no certifica esas condiciones.

Fuentes: [LoginRequest.php](../app/Http/Requests/Auth/LoginRequest.php), [StoreReservaRequest.php](../app/Http/Requests/StoreReservaRequest.php), [AppServiceProvider.php](../app/Providers/AppServiceProvider.php), [EnsureAccountIsActive.php](../app/Http/Middleware/EnsureAccountIsActive.php), [EnsureBookingWorkflowIsReady.php](../app/Http/Middleware/EnsureBookingWorkflowIsReady.php), [bootstrap/app.php](../bootstrap/app.php).

## 10. Paneles, calendario y herramientas operativas

El panel del cliente muestra su próxima reserva pendiente/confirmada cuyo horario todavía no terminó, incluyendo un partido en curso. Explica el plazo de pago y permite abrir el ticket. Si no hay próximo partido, ofrece reservar.

El panel del personal muestra hasta 30 reservas de hoy y hasta ocho pagos pendientes, con enlaces al listado completo. Incluye conteos, ocupación semanal, importes aprobados, cancelaciones/rechazos, canchas y horas con más reservas. El calendario distingue libre, reservado pendiente, reservado pagado y mantenimiento, y consulta cambios cada 30 segundos.

Si se desactiva la publicación automática, el personal recibe un aviso cuando no hay horarios futuros de canchas operativas o cuando la última fecha publicada no llega a seis días después de hoy. Administración tiene un enlace para publicar manualmente; recepción recibe la indicación de solicitarlo. El aviso comprueba la última fecha, no la existencia de cupos en cada día intermedio. Con automatización activada se avisa si faltan las tarifas necesarias.

**Alcance de los importes:** las métricas por período agrupan pagos aprobados según la fecha de creación de la reserva, no la fecha real de cobro. No existe un cierre de caja, conciliación bancaria ni registro de devolución integrado. Los rankings cuentan reservas y no equivalen necesariamente a partidos jugados; cancelaciones/rechazos no prueban ausencias.

Las sumas de cobros excluyen `pago_demo=true`. Las reservas de prueba siguen visibles, identificadas, y ocupan cupos durante la demostración.

### Comandos incluidos

| Comando | Función y alcance |
|---|---|
| `reservas:liberar-vencidas` | Cancela pagos pendientes vencidos, libera horarios y restaura mantenimientos vencidos |
| `reservas:enviar-avisos` | Genera recordatorios y procesa avisos configurados |
| `horarios:publicar` | Completa la ventana de fechas futuras según reglas y tarifas automáticas; conserva horarios y reservas existentes |
| `club:backup` | Crea respaldo privado de MySQL o SQLite; no restaura automáticamente |
| `club:verificar-entorno` | Detecta faltantes de configuración y condiciones de producción; no certifica el despliegue |
| `club:crear-admin` | Crea administrador desde consola con contraseña solicitada de forma privada |
| `club:alinear-datos` | Previsualiza corrección a arcilla y horarios del club; `--aplicar` la guarda. No cancela reservas fuera del horario nuevo: informa su cantidad para revisión |

El comando de alineación indica que se debe contar con respaldo previo; no lo crea por sí mismo. El respaldo y su restauración son operaciones separadas.

Fuentes: [DashboardController.php](../app/Http/Controllers/DashboardController.php), [CalendarioController.php](../app/Http/Controllers/CalendarioController.php), [calendario/index.blade.php](../resources/views/calendario/index.blade.php), [app/Console/Commands](../app/Console/Commands).

## 11. Qué es real, configurable o de demostración

| Parte | Estado demostrado por el código |
|---|---|
| Registro, acceso, permisos y perfil | Implementados |
| Disponibilidad, creación, cambio y cancelación | Implementados sobre registros de base |
| Ticket, QR, PDF y registro de ingreso | Implementados; el QR consulta estado y staff registra uso |
| Administración, calendario, mantenimiento y reglas | Implementados |
| Publicación diaria de horarios | Implementada; se ejecuta al abrir páginas operativas y dispone de comando programado |
| Auditoría | Implementada con detalles parciales; no historial completo de cada campo |
| Efectivo | Registro y confirmación manual del cobro |
| Tarjeta online | Simulador propio de aprobación/rechazo; sin conexión a bancos ni Culqi |
| Yape | Opción configurable con referencia; sin conexión bancaria ni aprobación automática |
| Pasarela, cobro en línea y reembolso | No implementados |
| Avisos y recordatorios | Registro y procesamiento implementados; entrega externa depende del transporte y planificador |
| Recuperación por correo | Implementada; requiere transporte externo para recibir el enlace fuera del entorno local |
| Verificación obligatoria del correo | No exigida por el modelo actual |
| Caja y contabilidad | Métricas parciales; no módulo de cierre/conciliación/devoluciones |
| Demostración | `DemoSeeder` crea cliente, cuatro canchas de arcilla, tarifas, horarios y reservas de ejemplo en una base vacía fuera de producción |
| Precios y reservas de ejemplo | No demuestran tarifas vigentes ni pagos reales del club |
| Home | Usa canchas de base; imágenes y textos comerciales no prueban certificaciones ni servicios externos |

El seeder incluye una reserva Yape aprobada de ejemplo. Eso no significa que las solicitudes reales se aprueben automáticamente. Tampoco se deduce la cantidad real de usuarios, canchas o reservas a partir del seeder.

Hay comentarios antiguos que describen Yape automático, reembolsos o relación histórica 1:1 entre horario y reserva. Este informe utiliza la implementación actual y las migraciones posteriores, que tienen un comportamiento diferente.

## 12. Qué queda por comprobar fuera de esta revisión

El repositorio contiene pruebas de autenticación, perfil, permisos, seguridad de reservas, flujo operativo, concurrencia y horarios. Su existencia no prueba correo externo, cobros reales ni funcionamiento del planificador del servidor.

Para confirmar esos puntos se necesitan pruebas del entorno: entrega y recuperación de correo con proveedor real, ejecución del planificador, acceso QR con la URL desplegada, restauración de una copia y concurrencia con MySQL. Aquí no se ejecutaron migraciones, seeders, envíos externos ni cambios de datos.

Fuentes: [tests/Feature](../tests/Feature), [VerificarEntorno.php](../app/Console/Commands/VerificarEntorno.php), [DemoSeeder.php](../database/seeders/DemoSeeder.php).
