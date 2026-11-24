<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Top Tennis</title></head><body style="font-family:Arial,sans-serif;background:#f1f5f0;padding:24px;color:#163426">
<div style="max-width:560px;margin:auto;background:white;padding:28px;border-radius:16px">
<h1 style="color:#15803d">Top Tennis</h1><h2>{{ ucfirst(str_replace('_', ' ', $aviso->evento)) }}</h2>
<p>Hola, {{ $aviso->datos['nombre'] }}.</p><p>Estos son los datos registrados para tu reserva:</p>
<p><strong>Código:</strong> {{ $aviso->datos['codigo'] }}</p><p><strong>Cancha:</strong> {{ $aviso->datos['cancha'] }}</p>
<p><strong>Fecha y hora:</strong> {{ $aviso->datos['inicio'] }} – {{ $aviso->datos['fin'] }} ({{ config('app.timezone') }})</p>
<p><strong>Precio:</strong> S/ {{ number_format($aviso->datos['precio'],2) }}</p><p><strong>Reserva:</strong> {{ $aviso->datos['estado'] }} · <strong>Pago:</strong> {{ $aviso->datos['pago'] }}</p>
@if($aviso->datos['motivo'])<p>{{ $aviso->datos['motivo'] }}</p>@endif
<p><a href="{{ route('reservas.ticket', $aviso->reserva_id) }}" style="color:#15803d">Consultar mi reserva</a> (requiere iniciar sesión).</p>
<p>Si ya pagaste una reserva cancelada, comunícate con el club para gestionar el saldo. Este aviso no confirma un reembolso.</p>
</div></body></html>
