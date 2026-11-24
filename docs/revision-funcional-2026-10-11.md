# Revisión funcional de Top Tennis — 11 de octubre de 2026

Esta revisión describe el estado anterior a las mejoras de operación. Las superficies, horarios por día, textos de pagos/reembolsos, próximo partido y agenda de recepción se actualizaron después. Consulta [el historial](../CHANGELOG.md) para conocer el estado más reciente; se conserva este análisis como evidencia de lo encontrado.

La aplicación tiene una base funcional para reservar y gestionar un club. Las mejoras más urgentes son la coherencia de los datos y mensajes, la claridad del pago y la información diaria de recepción. Agregar más módulos antes de resolver eso aumentaría la confusión.

## Qué se revisó y con qué límites

- Se revisaron el código, las pantallas y la configuración no secreta de la instalación actual. Las consultas a MySQL fueron solo de lectura.
- Se recorrió la interfaz con cuentas ficticias de cliente y administrador en una copia SQLite separada. No se crearon reservas ni se cambiaron usuarios reales.
- Como cliente: inicio de sesión, selección de día/hora/cancha, resumen, creación de una reserva pendiente, ticket, modificación del horario, historial y cierre de sesión. La modificación conservó el código y actualizó el plazo de pago.
- Como administrador: panel, reservas, reglas, calendario, horarios, usuarios, auditoría, formulario de reserva manual y tarifas. Se inspeccionaron vistas de móvil a 390 px.
- Resultado automatizado: **61 pruebas aprobadas y 261 comprobaciones**. Incluyen permisos, aislamiento entre clientes, cancelación, verificación de pago, QR, mantenimiento y una prueba simultánea en SQLite.
- La herramienta del navegador se bloqueó en la confirmación nativa de «Verificar pago». Esa aprobación no se completó manualmente; su comportamiento de servidor está cubierto por pruebas. La cancelación tampoco se completó interactivamente en esta revisión.
- No se probó entrega de correo real, una pasarela real, restauración del respaldo, concurrencia sobre MySQL ni todos los dispositivos físicos. No es una certificación de seguridad o de producción.

## Problemas y mejoras que deben ir primero

| Prioridad | Hallazgo | Acción propuesta |
|---|---|---|
| Alta | La home presenta arcilla como identidad del club, pero MySQL contiene también Dura y Césped Artificial. | Confirmar las características físicas y corregir los registros o el mensaje público con autorización. Conservar el historial; no borrar reservas. |
| Alta | La home anuncia sábado hasta las 20:00 y domingo hasta las 15:00. Las reglas reales usan un único cierre a las 22:00 para los siete días. | Configurar apertura y cierre por día de semana y utilizar esa misma configuración en reservas y home. Revisar los horarios ya publicados y sus reservas antes de ajustar datos. |
| Alta | El panel del cliente ofrece Yape aunque actualmente está desactivado. «Confirmar y pagar» tampoco describe lo que hace el formulario. | Mostrar solo medios activos y cambiar el título a «Revisa y solicita tu reserva». Mantener explícito el estado pendiente y el plazo exacto de pago. |
| Alta | El modal de mantenimiento promete registrar un reembolso. La operación conserva el pago y no procesa una devolución. | Corregir el texto y añadir un seguimiento de devoluciones manuales, con importe, responsable, fecha y estado. |
| Alta | La «tasa no-show» calcula anulaciones de pago, no ausencias de jugadores. Los ingresos por día se agrupan por creación de la reserva, no por fecha del cobro. | Registrar asistencia y fecha de cobro por separado. Calcular las métricas con esos eventos y distinguir importe reservado, cobrado y devuelto. |
| Media | El panel dice «286 horarios disponibles» en la copia: es un total de cupos por cancha para varios días, no 286 horas diferentes ni cupos de hoy. La pantalla de horarios también cuenta como disponibles registros cuyo inicio ya pasó. | Separar hoy, próximos siete días, ocupados y pasados; explicar si el contador mide cupos o franjas horarias. |
| Media | Cambiar una reserva abre un selector con cientos de opciones mezclando días, horas y canchas. | Reutilizar el recorrido día → hora → cancha y mostrar el precio anterior y nuevo antes de guardar. |
| Media | Se configura duración variable, pero las tarjetas de disponibilidad siempre muestran «/hr». | Mostrar importe total y duración real: por ejemplo «S/ 50 por 90 minutos», únicamente cuando esos sean los datos del horario. |
| Media | La selección de horarios se actualiza localmente al pasar el tiempo, pero no consulta si otro usuario acaba de ocupar un cupo. El servidor sí vuelve a validar al guardar. | Refrescar disponibilidad sin perder las selecciones válidas y explicar el conflicto con alternativas cercanas. |
| Media | En la copia SQLite, las fechas del historial se mostraron cinco horas adelantadas. MySQL actualmente coincide con Lima. | Uniformar escritura y visualización de fechas para que auditoría y reservas mantengan la misma zona también al cambiar de entorno. |

Referencias: `resources/views/dashboard.blade.php`, `app/Http/Controllers/DashboardController.php`, `resources/views/reservas/confirmar.blade.php`, `resources/views/reservas/disponibles.blade.php`, `resources/views/reservas/edit.blade.php`, `app/Models/BookingEvent.php` y `app/Services/ReglasReserva.php`.

## Si yo fuera cliente

El recorrido principal se entiende: elijo un día, una hora y una cancha; veo el importe, guardo la solicitud y recibo un ticket pendiente. El cambio de horario funcionó y el historial conservó la reserva. Lo que falta es saber, de un vistazo, qué debo hacer ahora.

1. **Mi próximo partido al entrar.** Una tarjeta con fecha, cancha, duración, importe, estado y un botón al ticket. Si está pendiente: «Paga antes del 12/10 a las 10:30». Si está confirmada: «Presenta tu QR en recepción».
2. **Condiciones antes de reservar.** Mostrar la hora exacta límite para pagar y para cambiar/cancelar. Evitar que el cliente conozca la política recién al intentar modificar.
3. **Avisos útiles.** Confirmación después de verificar el pago, cambios, cancelaciones y recordatorio del partido. El correo actual funciona en modo local y los avisos están desactivados: todavía no llegan al buzón.
4. **Cambiar horario con la misma experiencia de reserva.** Días y horas claras, sin una lista enorme. Para reservas pagadas, mostrar alternativas del mismo precio y explicar cómo gestionar diferencias.
5. **Recordar mi teléfono.** Poder actualizarlo en el perfil y precargarlo al reservar. El perfil actual prioriza nombre e ícono, pero el contacto ayuda más a coordinar un partido.
6. **Llegar sin dudas.** Dirección, enlace al mapa y contacto del club también en el ticket. Añadir «Guardar en mi calendario» y «Repetir reserva» como mejoras posteriores.

La pantalla de reservas debería separar próximas y anteriores. Las canceladas deben conservarse, pero no competir visualmente con el siguiente partido. El filtro «código o cliente» puede simplificarse para el cliente, que solo consulta sus propias reservas.

## Si yo fuera administrador

Ya puedo consultar reservas, filtrar clientes, gestionar canchas y tarifas, generar horarios, configurar reglas y revisar acciones. Pero al abrir el sistema necesito resolver lo urgente antes de mirar estadísticas generales.

1. **Agenda de hoy.** Ordenada por hora, con cancha, jugador, teléfono, pago y asistencia. Accesos rápidos al ticket, cobro y registro de ingreso.
2. **Bandeja de pagos pendientes.** Cantidad, importe, vencimiento y responsable de la verificación. Priorizar los que están próximos a vencer.
3. **Caja diaria.** Registro de cobros y devoluciones con fecha real, método, referencia y usuario responsable. Exportación CSV y cierre de caja. El ticket de reserva debe distinguirse del documento de pago.
4. **Asistencia.** Estados «Llegó», «No se presentó» y «Partido completado». Aprovechar el ingreso con QR que ya existe; no llamar ausencia a una cancelación.
5. **Horarios por día y excepciones.** Sábado y domingo con cierres diferentes, festivos y bloqueos concretos por mantenimiento o actividades. Mostrar reservas afectadas antes de aplicar un cambio.
6. **Ficha del cliente.** Historial, próximos partidos, contacto y notas internas controladas. Advertir antes de desactivar o eliminar una cuenta con reservas futuras y permitir revisar qué ocurrirá con ellas.
7. **Estado de los servicios.** Avisos sin enviar, errores de correo, última ejecución de tareas y fecha del último respaldo. Para una operación pequeña, esto aporta más que añadir otros gráficos.

## Diseño y experiencia

La home y el flujo de disponibilidad tienen una base clara y adaptable. En las pantallas administrativas revisadas no hubo desbordamiento general a 390 px; Usuarios usa desplazamiento horizontal dentro de la tabla. Para recepción en celular, las tarjetas con acciones visibles serían más cómodas.

Conviene unificar «Top Tennis», «Top Tennis Digital» y «Top Tennis Perú», y usar el mismo tono peruano: «Ingresa», en lugar de «Ingresá». También simplificar textos técnicos como «slots» y acciones de auditoría como «modificar».

La leyenda «Pasada / Reservada / Disponible» ya no corresponde al listado del cliente, que ahora muestra solo cupos libres y futuros. Bastaría una explicación de lo que se está mostrando. No debe parecer que hay un contador en vivo si no se consultan cambios del servidor.

Las confirmaciones de acciones importantes deberían explicar la consecuencia y permitir cancelar cómodamente. En mantenimiento, mostrar el número de reservas afectadas y advertir que los reembolsos requieren gestión real. Revisar teclado, foco de modales, etiquetas de controles y contraste en una pasada específica de accesibilidad.

## Base técnica y operación

Hay controles valiosos: permisos en el servidor, protección del rol al registrarse, precio conservado en la reserva, rechazo de duplicados y solapamientos, transacciones, historial y validación de QR. Las pruebas aprobaron los escenarios cubiertos; eso no demuestra que todos los escenarios posibles estén resueltos.

Antes de usar el sistema con clientes externos: configurar y probar recuperación de contraseña por correo; verificar las tareas que vencen reservas y envían recordatorios; probar restauración de una copia en otra base; repetir concurrencia en MySQL; revisar HTTPS y configuración de producción. Para el administrador, añadir una segunda verificación de acceso sería una mejora posterior de seguridad.

## Orden recomendado

1. Coherencia de superficies, horario por día, pagos, reembolsos y métricas.
2. Próximo partido del cliente, agenda diaria de recepción y pagos pendientes.
3. Correo real, recordatorios y comprobaciones de operación.
4. Caja, asistencia, devoluciones y exportaciones.
5. Repetir reserva, calendario personal, lista de espera o membresías, solo si el uso real los justifica.

No empezaría con puntos, torneos o una aplicación móvil independiente. Primero haría que el cliente entienda su reserva y que recepción pueda atender el día sin buscar entre varias pantallas.
