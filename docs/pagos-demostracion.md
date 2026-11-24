# Pagos online de demostración

Actualizado el 13 de octubre de 2026.

## Recorrido del cliente

1. Elegir día, hora y cancha.
2. Seleccionar Yape o tarjeta de débito/crédito. Efectivo no está disponible online.
3. Continuar al pago de prueba: no utiliza el QR personal de ejemplo ni solicita datos bancarios.
4. Simular aprobación o rechazo; un rechazo permite reintentar dentro del plazo existente.
5. Abrir el ticket o PDF de demostración: no acredita cobro real ni autoriza ingreso.

Las reservas de prueba ocupan horarios mientras están activas. Se pueden cancelar; las pendientes vencen y liberan la cancha. No se borran reservas anteriores.

## Separación de cobros reales

- `pago_demo` se decide en el servidor, no desde el navegador.
- Los importes de prueba no se suman a cobros aprobados del panel, gráfico ni listado del personal.
- No se generan correos ni recordatorios para reservas de prueba.
- Recepción no puede convertirlas en cobros reales con la acción de confirmar pago.
- Efectivo y Yape presencial siguen disponibles en el formulario manual del personal.
- Desactivar el simulador no borra la identificación de los tickets de prueba ni autoriza su ingreso.

## Configuración

`PAGOS_DEMO_ENABLED=true` habilita el simulador exclusivamente en `local` y `testing`. En producción se bloquea aunque esa variable sea verdadera.

La migración `2026_10_13_120000_add_demo_payments_to_reservas.php` añade Tarjeta y la marca de demostración; conserva Efectivo para historial y recepción. Su reversión se bloquea si existen pruebas para impedir que pierdan su identificación.

Con el simulador desactivado, solo se ofrece Yape si `YAPE_ENABLED=true`; ese recorrido es manual y requiere comprobación del personal. Sin métodos habilitados, se muestra un aviso; no se ofrece Efectivo como alternativa online.

## Pendiente para cobros reales

Este es un **simulador propio**, no una integración con Culqi ni su entorno de pruebas. No hay tokenización de tarjetas, conexión bancaria, cobro ni devolución automática.

Para cobrar se necesita una cuenta comercial del club y credenciales del proveedor. Después se integran su formulario seguro, confirmación desde el servidor, notificaciones y gestión de pagos tardíos. No se guardan claves en Git.

Fuentes: [ServicioPago.php](../app/Services/ServicioPago.php), [PagoDemoController.php](../app/Http/Controllers/PagoDemoController.php), [StoreReservaRequest.php](../app/Http/Requests/StoreReservaRequest.php), [GestorReservas.php](../app/Services/GestorReservas.php), [DemoPaymentTest.php](../tests/Feature/DemoPaymentTest.php).
