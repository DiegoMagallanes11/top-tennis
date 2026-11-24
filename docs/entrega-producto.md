# Top Tennis: instalación, pruebas y entrega

## Estado actual

Se implementó el flujo del cliente y del personal sobre Laravel y la base existente. Las reservas se guardan en la base de datos; no se utiliza almacenamiento del navegador para simularlas.

**La nueva migración se aplicó a tu MySQL actual el 11 de octubre de 2026, con tu autorización explícita.** Se creó un respaldo nuevo antes de ejecutarla. No se borraron datos reales ni se cambiaron contraseñas existentes.

La comprobación posterior confirmó que todos los campos anteriores se conservaron íntegramente: 3 usuarios, 4 canchas, 2 tarifas, 336 horarios y 2 reservas. Los campos y tablas nuevos ya están disponibles; la protección por migración pendiente deja de bloquear las operaciones del club.

Antes de la actualización se creó esta copia privada:

`storage/app/private/backups/club-20261011-194853-ed9e0d.sql`

El archivo contiene datos privados. No lo publiques ni lo subas al repositorio. La prueba de restauración de esa copia todavía está pendiente.

### Actualización de operación del 11 de octubre

Consulta [CHANGELOG.md](../CHANGELOG.md) para el historial de entregas y comprobaciones. Se guardó una versión inicial local de Git y se conserva el repositorio existente. La confirmación del responsable permitió corregir las cuatro canchas a Arcilla y el cierre por día: Lun–Vie 22:00, sábado 20:00 y domingo 15:00, con apertura 06:00.

Respaldo previo a esa corrección: `storage/app/private/backups/club-20261011-215252-627eec.sql`. Se conservaron íntegramente 4 usuarios, 336 horarios, 2 tarifas y 3 reservas. El comando `club:alinear-datos` muestra una revisión sin escribir; `--aplicar` guarda la corrección y un registro de la operación. No cancela reservas existentes. No lo ejecutes para reemplazar una configuración posterior personalizada sin revisarla.

El panel del cliente muestra su próximo partido y el plazo de pago. El personal ve la agenda diaria y los pagos que vencen antes. Los horarios de atención de la home se obtienen de las mismas reglas que la disponibilidad. Las métricas de importes por creación de reserva no son un cierre de caja; ese módulo y las devoluciones reales siguen pendientes.

## Qué puedes hacer

- Cliente: registrarse, entrar, salir, recuperar contraseña con un enlace temporal, consultar disponibilidad, guardar una reserva, consultar su ticket e historial, cambiar el horario y cancelar dentro del plazo del club.
- Administrador: gestionar usuarios, recepción, canchas, tarifas y horarios; filtrar reservas por fecha, cliente, cancha, pago y estado; verificar cobros, rechazar reservas y marcar partidos finalizados como completados; consultar estadísticas, historial de clientes, reglas y registro de acciones.
- Recepción: calendario, reservas manuales, verificación de cobros y registro de ingresos por QR. No puede administrar usuarios ni reglas del club.

Los permisos se comprueban en el servidor. Los clientes solo acceden a sus propias reservas. Los cambios y las cancelaciones conservan su historial. Los horarios se liberan al cancelar o vencer un pago pendiente.

## Reglas de reservas

Desde **Reglas del club** se configuran apertura, cierre, días laborables, festivos, descansos, anticipación mínima y máxima, duración de nuevos horarios, margen entre partidos y plazo de cancelación. La zona horaria se configura con `APP_TIMEZONE`, inicialmente `America/Lima`.

Cada cancha tiene capacidad de una reserva simultánea; Singles/Dobles describe la modalidad de juego. El precio de la reserva se guarda como una copia del precio vigente. Los cambios de una reserva pagada requieren el mismo precio; las diferencias y los reembolsos se gestionan en caja.

Los pagos del cliente comienzan pendientes, incluidos los de Yape. La recepción debe comprobar que recibió dinero antes de aprobarlos. El número de operación no confirma el pago. El plazo de verificación vence 30 minutos antes del inicio. Una reserva manual del personal registra un cobro comprobado en caja.

Estados de reserva: `pendiente`, `confirmada`, `cancelada`, `rechazada`, `completada`. El estado del pago se conserva por separado; cancelar no significa que se haya reembolsado dinero.

La creación y modificación bloquean los recursos en una transacción y comprueban nuevamente disponibilidad y solapamientos. La clave de solicitud permite repetir el envío del cliente sin duplicar la reserva.

## Instalar una copia nueva

Necesitas PHP compatible con `composer.json` (8.3 o posterior), Composer, Node compatible con Vite y una base MySQL o SQLite. PHP debe tener las extensiones exigidas por Composer; para las pruebas también necesita `mbstring` y `pdo_sqlite`.

1. Ejecuta `composer install` y `npm ci --ignore-scripts`.
2. Copia `.env.example` a `.env` y configura una base propia. No reutilices una base de otro cliente.
3. Ejecuta `php artisan key:generate` solo si es una instalación nueva sin `APP_KEY`.
4. Ejecuta `php artisan migrate`. No uses `migrate:fresh` ni `migrate:reset` sobre datos existentes.
5. Ejecuta `npm run build`.
6. Crea al administrador con `php artisan club:crear-admin tu-correo@ejemplo.com --nombre="Administrador"`. La contraseña se pide en privado; no hay contraseñas predeterminadas.
7. Inicia con `run-app.bat` en Windows o `php artisan serve`. Consulta `http://127.0.0.1:8000`.

El administrador crea las cuentas de recepción desde **Usuarios**. Los clientes se registran desde la web. Si utilizaste los seeders antiguos, cambia las contraseñas de esas cuentas antes de publicar; eliminarlas del código no cambia las contraseñas ya guardadas.

## Actualizar tu instalación existente

Primero revisa el destino de `.env` y autoriza la actualización de esa base. Después:

```powershell
php artisan club:backup
php tests/support/verify-existing-data.php before
php artisan migrate
php tests/support/verify-existing-data.php after
npm run build
php artisan club:verificar-entorno
```

La migración agrega campos de estado/contacto/solicitud y tablas de reglas, acciones y avisos. No elimina las tablas ni los campos existentes. Clasifica el estado nuevo usando el estado anterior; revisa los pagos históricos que anteriormente se aprobaron sin verificación real.

`verify-existing-data.php` compara todos los campos anteriores y la cantidad de registros de usuarios, canchas, tarifas, horarios y reservas. No muestra sus datos personales. `migrate.bat` permite actualizar con respaldo previo y ya no ofrece borrar toda la base.

## Demostración separada

`DatabaseSeeder` no introduce cuentas ni reservas automáticamente. `DemoSeeder` exige una base vacía fuera de producción. Crea canchas, tarifas y reservas de ejemplo identificadas como demostración, sin cuentas administrativas ni contraseñas compartidas.

Configura un `.env` propio que apunte a una base de demostración separada y ejecuta:

```powershell
php artisan migrate
php artisan db:seed --class=DemoSeeder
php artisan club:crear-admin tu-correo-de-demostracion@ejemplo.com
```

La comprobación visual de este trabajo se hizo en `storage/framework/testing/browser-demo-20261011.sqlite`, con cuentas y contraseñas aleatorias, correo en modo local y un aviso visible de datos de prueba. Esa base no debe publicarse como base de un club real.

## Correo, avisos y recordatorios

Configura en `.env` el transporte de correo, servidor, puerto, usuario, contraseña y remitente que te dé tu proveedor. No pongas credenciales en las vistas ni en el repositorio.

```env
MAIL_MAILER=smtp
MAIL_HOST=servidor-del-proveedor
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=reservas@tu-dominio
MAIL_NOTIFICATIONS_ENABLED=true
MAIL_REMINDER_HOURS=24
```

El correo de recuperación usa el mecanismo de enlaces temporales de Laravel. En modo `log` no se entrega a ningún buzón y la pantalla lo indica. Las respuestas de recuperación no revelan si el correo pertenece a una cuenta.

Los avisos de creación, cambios, cancelaciones, rechazo y verificación se guardan en la base, dentro de la operación. Se procesan con `php artisan reservas:enviar-avisos`; los fallidos se reintentan hasta tres veces. El estado `enviado` significa que el servidor de correo aceptó el mensaje, no que una persona lo leyó. Los avisos `sin_configurar` no se envían retroactivamente al activar el servicio.

El comando también prepara un recordatorio para reservas confirmadas dentro del plazo configurado. Ejecuta `php artisan schedule:work` en desarrollo o programa `php artisan schedule:run` cada minuto en producción. Esto también libera reservas vencidas y restaura canchas después del mantenimiento.

Si un proceso se interrumpe cuando un aviso está `enviando`, comprueba el proveedor antes de reintentarlo para evitar un correo duplicado. Esta recuperación requiere revisión del operador.

## Pagos externos: pendiente de integración

Actualmente solo está implementada la comprobación manual en recepción. **No se integró ni se probó una pasarela real**, porque no hay cuenta, proveedor elegido ni credenciales de pruebas disponibles. No hay cobros de tarjeta ni datos de tarjetas almacenados.

Yape se desactiva por defecto. Para ofrecerlo como pago manual, el club debe proporcionar su QR real, configurar `YAPE_QR_PATH`, activar `YAPE_ENABLED=true` y comprobar cada cobro en su cuenta. No uses la imagen de ejemplo para cobrar a clientes.

Para una pasarela faltan: proveedor y cuenta comercial, claves de pruebas guardadas en `.env`, URL pública HTTPS para eventos, secreto de verificación y pruebas con eventos firmados. Debe verificarse el evento y el importe en el servidor; la redirección del navegador no basta. No cambies `PAGOS_DRIVER=manual` hasta implementar y verificar esa integración.

## Pruebas realizadas y pendientes

Resultado actual: **70 pruebas aprobadas, con 308 comprobaciones**. También se prepararon todas las vistas y se compiló la versión de producción de los estilos y scripts. La entrega de operación añade pruebas de horarios por día, paneles, privacidad y conservación de reservas al corregir los datos del club.

Las pruebas automatizadas verifican registro/inicio/cierre de sesión, recuperación con token válido e inválido, permisos, persistencia, pagos pendientes, reintentos de solicitud, cambios de horario, cancelación, mantenimiento, reglas, duración, historial, filtros, fallos de escritura y reintentos de correo.

Una prueba abre **dos procesos reales contra una base SQLite temporal**, con horarios superpuestos de una misma cancha. Solo uno puede guardar la reserva. En SQLite se usa una transacción inmediata; en MySQL se bloquean las filas del recurso.

Para repetir la suite con las extensiones disponibles en este equipo:

```powershell
php -d extension=mbstring -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit
npm run build
```

Se revisaron en navegador el acceso administrativo, la reserva manual y su persistencia al recargar, las pantallas de reservas y calendario, la navegación móvil y la ausencia de errores de consola en esas páginas. La vista de reservas y el calendario no mostraron desbordamiento horizontal en 390 px. Hay evidencia visual en `docs/screenshots/reservas-demo.png`.

La confirmación nativa de cancelación bloqueó la herramienta de navegador; no se pudo completar esa cancelación manual ni las siguientes comprobaciones interactivas del cliente en esa sesión. Sus operaciones de servidor están cubiertas por pruebas automatizadas. Verifica manualmente: cancelar y aceptar la confirmación; entrar con un cliente, reservar, cambiar horario y cerrar sesión; comprobar la liberación en otra sesión.

La migración en MySQL y la comprobación de integridad se completaron. El chequeo de entorno local terminó sin detectar requisitos esenciales incumplidos; no sustituye las pruebas externas.

También están pendientes: repetir la prueba simultánea sobre una **base MySQL de pruebas separada**; restaurar el respaldo en una base vacía; entrega real de recuperación, confirmación y recordatorio; pruebas de la pasarela cuando exista; navegación por teclado y dispositivos físicos.

## Antes de publicar

Usa `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, cookies seguras (`SESSION_SECURE_COOKIE=true`) y sesiones cifradas (`SESSION_ENCRYPT=true`). Usa una cuenta limitada para MySQL y apunta el servidor web a `public/`, nunca a la raíz del proyecto. Conserva `APP_KEY` al actualizar una instalación existente.

Ejecuta `php artisan club:verificar-entorno` para detectar configuraciones esenciales pendientes. Compila recursos locales con `npm run build`; calendario y gráficos se cargan solo donde se necesitan. Configura y verifica el scheduler, correo, backups fuera del servidor y restauración antes de entregar.

Los backups contienen información personal y credenciales cifradas/hash de usuarios; protégelos y limita quién puede descargarlos. Define la política de conservación de datos y avisos de privacidad del club antes de publicar. El registro de acciones muestra eventos reales; no se rellenó con acciones históricas inventadas.

Puede haber costes de dominio, alojamiento, base de datos, correo y comisiones de pasarela. Dependen de los proveedores y no se contrataron servicios durante este trabajo.

**No se certifica que el producto esté listo para producción:** faltan las pruebas externas y la comprobación de despliegue indicadas arriba.
