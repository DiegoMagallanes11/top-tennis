<?php

namespace App\Services;

use App\Models\BookingEvent;
use App\Models\BookingNotification;
use App\Models\Reserva;

class RegistroReserva
{
    public static function guardar(Reserva $reserva, string $accion, array $datos = []): void
    {
        BookingEvent::create(['reserva_id' => $reserva->id, 'actor_id' => auth()->id(), 'accion' => $accion, 'datos' => $datos]);
        if ($reserva->pago_demo) {
            return;
        }
        $reserva->loadMissing('user', 'horario.cancha');
        $email = $reserva->user?->email;
        if (! $email || str_ends_with($email, '@toptennis.local')) {
            return;
        }
        $h = $reserva->horario;
        BookingNotification::create([
            'reserva_id' => $reserva->id, 'evento' => $accion, 'destinatario' => $email,
            'estado' => config('mail.notifications_enabled') ? 'pendiente' : 'sin_configurar',
            'datos' => [
                'codigo' => $reserva->codigo_validacion, 'nombre' => $reserva->user->name,
                'cancha' => $h?->cancha?->nombre, 'inicio' => $h?->hora_inicio?->format('d/m/Y H:i'),
                'fin' => $h?->hora_fin?->format('H:i'), 'precio' => $reserva->monto_pagado,
                'estado' => $reserva->estado_reserva, 'pago' => $reserva->estado_pago, 'motivo' => $reserva->motivo,
            ],
        ]);
    }
}
