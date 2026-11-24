# Historial de la publicación de portafolio

## 2026-11-23

- Se preparó una copia pública del estado local de Top Tennis, sin historial antiguo, base de datos, archivos de entorno privados, dependencias instaladas ni QR de cobro personal.
- Se conservaron la procedencia del código y los créditos en `UPSTREAM.md`.
- Se reorganizó el README para explicar el problema, los aportes, la instalación, los límites y seis capturas de la aplicación real con datos ficticios.
- Se corrigió `OwnReservation` para aceptar un modelo enlazado por Laravel o un identificador. Motivo: el modelo se pasaba a `find()` como una colección y provocaba errores en las rutas del cliente.
- Verificación: 87 pruebas de PHPUnit aprobadas y 446 comprobaciones en SQLite; seis capturas en navegador sin errores de JavaScript durante el recorrido.
- Pendiente: integración de pagos y correo real, comprobación de concurrencia en MySQL y validación del despliegue y restauración de respaldos.

Esta copia no sustituye ni modifica la carpeta de trabajo original, que mantiene su historial y los cambios locales existentes.
