# Preguntas Frecuentes — Top Tennis

> Respuestas breves a las preguntas más comunes sobre el diseño, las decisiones técnicas y el funcionamiento interno del sistema. Cada respuesta enlaza al archivo donde vive la implementación.
>
> Documentación relacionada: [Arquitectura](arquitectura.md) · [Modelo de datos](modelo-de-datos.md) · [Reglas de negocio](reglas-de-negocio.md) · [Índice de código](../INDEX.md)

---

## Arquitectura

### ¿Por qué los controladores no tienen la validación adentro?
Porque la validación vive en clases **FormRequest** dedicadas ([app/Http/Requests/](../app/Http/Requests/)). El controlador recibe los datos ya validados con `$request->validated()`. Esto mantiene los controladores delgados (*skinny controllers*), hace las reglas reutilizables y separa responsabilidades: el FormRequest valida, el controlador orquesta.

### ¿Dónde vive la lógica de negocio si no está en los controladores?
En los **modelos**, como scopes y métodos con nombre de negocio: `Horario::reservables()`, `Reserva::liberarVencidas()`, `Cancha::restaurarVencidas()`, `Reserva::qrSvg()`. El controlador solo coordina: valida → transacciona → responde. Ver [arquitectura.md](arquitectura.md).

### ¿Qué camino sigue una petición HTTP?
`routes/web.php` (URL + middleware) → `RoleMiddleware` (rol) → `FormRequest` (validación) → Controller (caso de uso, transacción, try/catch) → Modelo Eloquent (reglas de dominio) → Vista Blade o redirect con mensaje flash. Diagrama completo en [arquitectura.md](arquitectura.md#estructura-de-capas).

### ¿Por qué no usaron una capa de servicios o repositorios?
Por la escala del proyecto: los modelos Eloquent ya encapsulan las reglas de dominio y los controladores son cortos. Agregar capas intermedias habría sido complejidad sin beneficio (los repositorios duplican lo que Eloquent ya resuelve).

---

## Base de datos

### ¿Por qué todas las tablas de negocio usan SoftDeletes?
Para que "eliminar" no destruya información: `delete()` solo llena `deleted_at`. Así una reserva vieja conserva su horario y su cancha aunque el administrador los haya dado de baja, y la auditoría e integridad histórica se mantienen. Ver [Cancha.php](../app/Models/Cancha.php), [modelo-de-datos.md](modelo-de-datos.md#constraints-clave).

### ¿Por qué `reservas.horario_id` no es UNIQUE si un horario solo admite una reserva?
Ese UNIQUE **existió y fue eliminado** por la migración [drop_unique_horario_id](../database/migrations/2026_06_27_030434_drop_unique_horario_id_from_reservas_table.php): como los borrados son lógicos, la reserva cancelada seguía en la tabla y el constraint impedía re-reservar el horario liberado. La regla "una reserva activa por horario" la garantiza el **UPDATE atómico condicional** en [ReservaController::store()](../app/Http/Controllers/ReservaController.php).

### ¿Qué es `monto_pagado` y por qué existe si la tarifa ya tiene precio?
Es un **snapshot**: el precio se congela al momento de reservar. Si el administrador cambia la tarifa después, los tickets emitidos y los ingresos históricos no se alteran. El dashboard suma `monto_pagado`, nunca el precio actual de la tarifa.

### ¿Por qué el precio está en `tarifas` y no en `canchas`?
Porque el precio depende de la **franja horaria**, no solo de la cancha (tarifa día vs. noche, ofertas). `horarios` actúa como tabla puente: cada slot une una cancha con una tarifa. Cambiar un precio no toca canchas ni reservas ya vendidas.

### ¿Por qué el rol es un enum de PHP y no una tabla `roles`?
Con solo dos roles fijos (`admin`, `cliente`), una tabla sería indirección innecesaria. El **backed enum** [Rol.php](../app/enums/Rol.php) (casteado en el modelo User) da tipado fuerte sin strings mágicos: el código compara `Rol::Admin`, no `'admin'`.

### ¿Qué pasa si se elimina una cancha que tiene horarios?
Dos defensas: el controlador lo **rechaza antes** con un mensaje claro ([CanchaController::destroy()](../app/Http/Controllers/CanchaController.php)) y, si algo se escapara, la FK `RESTRICT` de la base de datos lo bloquea (el `QueryException` se captura y se muestra un mensaje amigable). Lo mismo aplica a tarifas y a horarios reservados.

---

## Concurrencia

### ¿Cómo evitan que dos usuarios reserven el mismo horario a la vez?
Con un **UPDATE atómico condicional** dentro de una transacción: `UPDATE horarios SET estado='reservado' WHERE id=? AND estado='disponible'`. La base de datos ejecuta esto de forma atómica; si el UPDATE afecta **0 filas**, otro usuario ganó el slot un instante antes y la transacción hace rollback con el mensaje "Ese horario acaba de ser reservado por otra persona". Ver [ReservaController::store()](../app/Http/Controllers/ReservaController.php).

### ¿Dónde usan `lockForUpdate` y por qué ahí?
En [Reserva::liberarVencidas()](../app/Models/Reserva.php): el proceso automático que anula no-shows bloquea las filas leídas para no chocar con una confirmación de pago simultánea del administrador (sin el lock, podría anularse una reserva que se estaba aprobando en ese mismo instante).

### ¿Qué operaciones van dentro de `DB::transaction` y por qué?
Las que tocan **más de una fila** y deben ser todo-o-nada: crear reserva + marcar horario (`store`, `storeManual`), cancelar reserva + liberar horario (`cancelar`), poner cancha en mantenimiento + cancelar reservas afectadas (`ponerMantenimiento`), anular vencidas + liberar horarios (`liberarVencidas`). Si algo falla a mitad, la BD queda como estaba.

---

## Procesos automáticos

### ¿Cómo se anulan las reservas que nunca se pagan (no-shows)?
Una reserva en Efectivo nace con `expira_at = hora_inicio − 30 min`. El comando `reservas:liberar-vencidas` corre **cada minuto** vía scheduler ([console.php](../routes/console.php)) y pasa las vencidas a `anulada` liberando su horario.

### ¿Y si el scheduler no está corriendo en la máquina?
Hay **doble disparo**: la misma lógica (`Reserva::liberarVencidas()` y `Cancha::restaurarVencidas()`) se ejecuta en "modo lazy" al inicio de las vistas clave (catálogo de reservas, confirmación, dashboard, canchas). La regla se cumple aunque solo exista tráfico web.

### ¿Cómo vuelve una cancha a estar operativa después del mantenimiento?
De dos formas: **automática** — al llegar `fin_mantenimiento`, `Cancha::restaurarVencidas()` la restaura en el siguiente request (modo lazy) — o **manual**, con el botón "Restaurar" del widget del dashboard ([CanchaController::restaurar()](../app/Http/Controllers/CanchaController.php)).

---

## Mantenimiento de canchas

### ¿Qué pasa con las reservas ya pagadas cuando una cancha entra a mantenimiento?
En una sola transacción: las reservas `aprobadas` cuyo juego cae dentro del periodo pasan a `cancelado_por_mantenimiento` y se **registra el reembolso** de cada una (código, cliente, monto) en el log. El mensaje de éxito indica cuántos reembolsos se procesaron. Ver [CanchaController::ponerMantenimiento()](../app/Http/Controllers/CanchaController.php).

### ¿Por qué el mantenimiento no está dentro del CRUD de canchas?
Porque son responsabilidades distintas: el CRUD administra el **catálogo** (crear/editar/eliminar) y el mantenimiento es un **proceso operativo** con reglas propias (reembolsos, restauración programada). Por eso tiene endpoints POST propios (`/canchas/{id}/mantenimiento`, `/restaurar`), validación propia y se dispara desde el widget "Estado de canchas" del dashboard — visible apenas el administrador inicia sesión.

---

## Seguridad y acceso

### ¿Cómo impiden que un cliente entre a las pantallas de administración?
Dos niveles: los links de administración **no se renderizan** en su navegación ([navigation.blade.php](../resources/views/layouts/navigation.blade.php)) y, si fuerza la URL, el middleware [RoleMiddleware](../app/Http/Middleware/RoleMiddleware.php) (`role:admin` en [web.php](../routes/web.php)) lo redirige al dashboard con "Acceso denegado".

### ¿Qué pasa si un cliente cambia el ID en la URL para ver un ticket ajeno?
[ReservaController::autorizar()](../app/Http/Controllers/ReservaController.php) verifica **dueño-o-admin** en cada acceso a una reserva concreta: además del rol, comprueba que `$reserva->user_id` sea el usuario autenticado. Autorización a nivel de objeto, no solo de ruta.

### ¿Por qué no se ven IDs en ninguna página?
Los IDs autogenerados no aportan al usuario y exponen información interna. Las URLs usan **route model binding** (Laravel resuelve el modelo y devuelve 404 si no existe) y las claves foráneas siempre se muestran por su **nombre legible** (`$horario->cancha->nombre`), nunca por el número.

### ¿Cómo se protegen los formularios?
Todos llevan token **CSRF** (`@csrf`); un token inválido o expirado produce la página 419 personalizada. Además, los campos pasan por la validación del FormRequest en el servidor — la validación HTML del navegador es solo la primera barrera.

### ¿Cómo se guardan las contraseñas?
Hasheadas con **bcrypt** (`Hash::make` / cast `hashed` del modelo User). Nunca en texto plano; el hash no es reversible. Los clientes creados "al vuelo" en la reserva manual reciben una contraseña interna aleatoria también hasheada.

---

## Manejo de errores

### ¿Por qué nunca aparece la pantalla de error cruda de Laravel?
Cuatro capas: (1) **try/catch en cada método** de los controladores, con `Log::error()` y redirect con mensaje en español; (2) **renderers globales** en [bootstrap/app.php](../bootstrap/app.php) que convierten ModelNotFound → 404, rutas inexistentes → 404, AuthorizationException → 403, TokenMismatch → 419 y QueryException → mensaje amigable; (3) **páginas de error personalizadas** en [resources/views/errors/](../resources/views/errors/); (4) `APP_DEBUG=false`, que garantiza que un error imprevisto muestre la página 500 propia y no un stack trace.

### ¿Dónde quedan registrados los errores?
En `storage/logs/laravel.log`, vía `Log::error()` con contexto (URL, usuario, mensaje). El usuario ve un mensaje claro; el detalle técnico queda en el log.

---

## Ticket, QR y PDF

### ¿Cómo generan el código QR si el servidor no tiene la extensión GD?
Con [bacon/bacon-qr-code](../composer.json) usando el backend **SVG** (`SvgImageBackEnd`): el QR se dibuja como vector, sin necesidad de GD ni Imagick. Se usa corrección de errores nivel **H** (~30 %) para tolerar el logo incrustado en el centro. Ver [Reserva::qrSvg()](../app/Models/Reserva.php).

### ¿Qué información contiene el QR?
Texto plano con los datos del ticket: código `TT-XXXX`, cliente, cancha, fecha, horario, monto, método y estado de pago ([Reserva::contenidoQr()](../app/Models/Reserva.php)). Cualquier lector de QR lo muestra; el personal lo coteja con el historial.

### ¿Cómo se genera el PDF del ticket?
[barryvdh/laravel-dompdf](../composer.json) renderiza la vista Blade [ticket-pdf.blade.php](../resources/views/reservas/ticket-pdf.blade.php) a un PDF A5 descargable ([ReservaController::descargarTicket()](../app/Http/Controllers/ReservaController.php)). Misma información que el ticket web, en formato imprimible.

---

## Pagos

### ¿Por qué Yape se aprueba al instante y Efectivo queda pendiente?
Porque en Yape el pago **ya ocurrió** (el cliente adjunta su N° de operación como comprobante); en Efectivo el pago ocurre después, en el mostrador, así que la reserva nace `pendiente` con fecha límite y el administrador la confirma al cobrar.

### ¿Qué evita que un mismo comprobante Yape pague dos reservas?
El constraint **UNIQUE** sobre `reservas.numero_operacion` más la validación previa en [StoreReservaRequest](../app/Http/Requests/StoreReservaRequest.php): el número se rechaza antes de tocar la base de datos si ya fue usado.

### ¿Por qué el plazo de gracia es de 30 minutos antes del juego?
Es la regla de negocio del club ([RN-01.4](reglas-de-negocio.md)): da margen para revender el slot si el cliente no se presenta a pagar. Está centralizada en la constante `Reserva::MINUTOS_GRACIA` — cambiar el plazo es tocar un solo número.

### ¿Cómo calculan los ingresos del dashboard?
Suma de `monto_pagado` de las reservas con `estado_pago = aprobado`, únicamente. Las pendientes, anuladas y canceladas no cuentan. Al ser un snapshot, los cambios de tarifa posteriores no distorsionan el histórico.

---

## Datos de prueba y ejecución

### ¿Cómo se genera el entorno de datos inicial?
`php artisan migrate:fresh --seed` ejecuta las migraciones y el [DatabaseSeeder](../database/seeders/DatabaseSeeder.php): 2 usuarios (admin y cliente), 4 canchas (una en mantenimiento), 2 tarifas (día/noche), horarios de 7 días (06:00–22:00) y 2 reservas de ejemplo (Yape aprobada y Efectivo pendiente).

### ¿Qué se necesita para correr el proyecto en otra máquina?
XAMPP (MySQL), PHP 8.3+, Composer. Pasos completos en el [README](../README.md#instalación-rápida-windows--xampp): `composer install`, copiar `.env`, `php artisan key:generate`, `migrate --seed` y `php artisan serve`. No requiere Node/npm para funcionar (Tailwind va por CDN) ni extensiones de imagen (el QR es SVG).
