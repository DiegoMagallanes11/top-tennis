@php
    $h = $reserva->horario;
    $cancha = optional($h?->cancha)->nombre ?? '-';
    $fecha  = optional($h?->hora_inicio)->format('d/m/Y') ?? '-';
    $hora   = optional($h?->hora_inicio)->format('H:i') . ' - ' . optional($h?->hora_fin)->format('H:i');
    $monto  = number_format((float) $reserva->monto_pagado, 2);
    $estado = ucfirst($reserva->estado_pago);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmación de reserva</title>
</head>
<body style="margin:0; padding:0; background:#f5f7f5; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7f5; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="520" cellpadding="0" cellspacing="0"
                       style="background:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0d3d22,#166534); padding:28px 32px;">
                            <p style="margin:0; color:#a3d94a; font-size:12px; letter-spacing:2px; text-transform:uppercase; font-weight:bold;">Top Tennis Digital</p>
                            <h1 style="margin:6px 0 0; color:#ffffff; font-size:22px;">¡Reserva confirmada!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 18px; font-size:15px;">Hola <strong>{{ $reserva->user?->name ?? 'jugador' }}</strong>, tu reserva quedó registrada. Estos son los detalles:</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                                <tr><td style="padding:8px 0; color:#6b7280;">Código</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $reserva->codigo_validacion }}</td></tr>
                                <tr><td style="padding:8px 0; color:#6b7280;">Cancha</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $cancha }}</td></tr>
                                <tr><td style="padding:8px 0; color:#6b7280;">Fecha</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $fecha }}</td></tr>
                                <tr><td style="padding:8px 0; color:#6b7280;">Horario</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $hora }}</td></tr>
                                <tr><td style="padding:8px 0; color:#6b7280;">Método de pago</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $reserva->metodo_pago }}</td></tr>
                                <tr><td style="padding:8px 0; color:#6b7280;">Estado</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $estado }}</td></tr>
                                <tr><td style="padding:8px 0; color:#6b7280; border-top:1px solid #eee;">Monto</td><td style="padding:8px 0; text-align:right; font-weight:bold; border-top:1px solid #eee;">S/ {{ $monto }}</td></tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px auto 4px;">
                                <tr><td style="border-radius:9999px; background:#15803d;">
                                    <a href="{{ $reserva->urlValidacion() }}" style="display:inline-block; padding:12px 28px; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none; border-radius:9999px;">Ver / verificar mi reserva</a>
                                </td></tr>
                            </table>

                            <p style="margin:18px 0 0; font-size:13px; color:#6b7280; line-height:1.5;">
                                Muestra el código <strong>{{ $reserva->codigo_validacion }}</strong> o el código QR de tu ticket al llegar al club.
                                También puedes verlo desde tu cuenta en la sección "Mis Reservas".
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9faf9; padding:18px 32px; text-align:center; border-top:1px solid #eee;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">Top Tennis Digital · Este es un correo automático, no respondas a este mensaje.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
