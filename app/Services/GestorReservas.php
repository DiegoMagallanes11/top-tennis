<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\ClubSetting;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestorReservas
{
    public function crear(User $cliente, array $datos, bool $cobradoEnCaja = false): Reserva
    {
        return DB::transaction(function () use ($cliente, $datos, $cobradoEnCaja) {
            $cliente = User::whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            if (! $cliente->activo || $cliente->esStaff()) {
                throw ValidationException::withMessages(['user_id' => 'Selecciona un cliente activo.']);
            }
            $h = Horario::findOrFail($datos['horario_id']);
            Cancha::whereKey($h->cancha_id)->lockForUpdate()->firstOrFail();
            $h = Horario::with('cancha', 'tarifa')->whereKey($h->id)->lockForUpdate()->firstOrFail();
            if (! empty($datos['clave_solicitud'])) {
                $existente = Reserva::withTrashed()->where('clave_solicitud', $datos['clave_solicitud'])->first();
                if ($existente) {
                    abort_unless($existente->user_id === $cliente->id, 403);
                    if ($existente->horario_id != $datos['horario_id'] || $existente->metodo_pago !== $datos['metodo_pago']) {
                        throw ValidationException::withMessages(['horario_id' => 'Esta solicitud ya fue usada. Abre un formulario nuevo.']);
                    }

                    return $existente;
                }
            }
            app(ReglasReserva::class)->validar($h);
            if (! $cobradoEnCaja && $h->hora_inicio->lte(now()->addMinutes(Reserva::MINUTOS_GRACIA))) {
                throw ValidationException::withMessages(['horario_id' => 'Reserva con más de 30 minutos de anticipación para verificar el pago.']);
            }
            $affected = Horario::whereKey($h->id)->where('estado', 'disponible')->update(['estado' => 'reservado']);
            if ($affected !== 1) {
                throw ValidationException::withMessages(['horario_id' => 'Otra persona acaba de reservar ese horario.']);
            }
            $r = Reserva::create([
                'user_id' => $cliente->id, 'horario_id' => $h->id, 'metodo_pago' => $datos['metodo_pago'],
                'numero_operacion' => $datos['metodo_pago'] === 'Yape' && ($cobradoEnCaja || ! ServicioPago::demoDisponible()) ? ($datos['numero_operacion'] ?? null) : null,
                'pago_demo' => ! $cobradoEnCaja && ServicioPago::demoDisponible() && in_array($datos['metodo_pago'], ['Yape', 'Tarjeta'], true),
                'estado_pago' => $cobradoEnCaja ? Reserva::ESTADO_APROBADO : Reserva::ESTADO_PENDIENTE,
                'estado_reserva' => $cobradoEnCaja ? 'confirmada' : 'pendiente', 'monto_pagado' => $h->tarifa->precio,
                'expira_at' => $cobradoEnCaja ? null : $h->hora_inicio->copy()->subMinutes(Reserva::MINUTOS_GRACIA),
                'clave_solicitud' => $datos['clave_solicitud'] ?? null, 'codigo_validacion' => Reserva::generarCodigoValidacion(),
                'contacto' => ['nombre' => $cliente->name, 'email' => $cliente->email, 'telefono' => $datos['telefono'] ?? $cliente->telefono],
            ]);
            RegistroReserva::guardar($r, $cobradoEnCaja ? 'creada_en_caja' : 'creada');

            return $r;
        }, 3);
    }

    public function cambiar(Reserva $reserva, string $accion, array $datos = []): Reserva
    {
        return DB::transaction(function () use ($reserva, $accion, $datos) {
            $resourceIds = [$reserva->horario->cancha_id];
            if ($accion === 'modificar') {
                $resourceIds[] = Horario::findOrFail($datos['horario_id'])->cancha_id;
            }
            Cancha::whereIn('id', $resourceIds)->orderBy('id')->lockForUpdate()->get();
            $r = Reserva::whereKey($reserva->id)->lockForUpdate()->firstOrFail();
            if (! in_array($r->horario->cancha_id, $resourceIds, true)) {
                throw ValidationException::withMessages(['general' => 'La reserva cambió mientras trabajabas. Actualiza la página y vuelve a intentarlo.']);
            }
            abort_unless(auth()->user()->can(in_array($accion, ['confirmar', 'rechazar', 'completar']) ? 'manage' : 'update', $r), 403);
            if (! $r->activa() || $r->estaVencida()) {
                throw ValidationException::withMessages(['general' => 'La reserva ya no está activa o su pago venció. Actualiza la página.']);
            }
            $h = $r->horario;
            if ($r->pago_demo && in_array($accion, ['confirmar', 'completar'], true)) {
                throw ValidationException::withMessages(['general' => 'Los pagos de demostración no se registran como cobros reales en recepción.']);
            }
            if (! auth()->user()->esStaff() && ($r->estaUsada() || now()->gte($h->hora_inicio->copy()->subHours(ClubSetting::reglas()['cancelacion_horas'])))) {
                throw ValidationException::withMessages(['general' => 'El plazo para cambiar o cancelar terminó. Comunícate con el club.']);
            }
            $antes = ['horario_id' => $r->horario_id, 'estado' => $r->estado_reserva, 'precio' => $r->monto_pagado];
            if ($accion === 'modificar') {
                $nuevo = Horario::findOrFail($datos['horario_id']);
                $ids = collect([$h->cancha_id, $nuevo->cancha_id])->unique()->sort();
                Cancha::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                $nuevo = Horario::with('cancha', 'tarifa')->whereKey($nuevo->id)->lockForUpdate()->firstOrFail();
                if ($nuevo->id === $h->id) {
                    return $r;
                }
                app(ReglasReserva::class)->validar($nuevo, $h->id);
                if ($r->estado_pago === Reserva::ESTADO_APROBADO && (string) $nuevo->tarifa->precio !== (string) $r->monto_pagado) {
                    throw ValidationException::withMessages(['horario_id' => 'Elige un horario del mismo precio para conservar el pago. Para diferencias de precio, contacta al club.']);
                }
                if ($r->estado_pago === Reserva::ESTADO_PENDIENTE && $nuevo->hora_inicio->lte(now()->addMinutes(Reserva::MINUTOS_GRACIA))) {
                    throw ValidationException::withMessages(['horario_id' => 'No queda tiempo suficiente para verificar el pago.']);
                }
                $nuevo->update(['estado' => 'reservado']);
                $h->update(['estado' => 'disponible']);
                $r->update(['horario_id' => $nuevo->id, 'monto_pagado' => $nuevo->tarifa->precio,
                    'expira_at' => $r->estado_pago === Reserva::ESTADO_PENDIENTE ? $nuevo->hora_inicio->copy()->subMinutes(Reserva::MINUTOS_GRACIA) : null]);
                $r->unsetRelation('horario');
            } elseif ($accion === 'confirmar') {
                if ($r->estado_pago !== Reserva::ESTADO_PENDIENTE) {
                    throw ValidationException::withMessages(['general' => 'Este pago ya está confirmado.']);
                }
                $r->update(['estado_pago' => Reserva::ESTADO_APROBADO, 'estado_reserva' => 'confirmada', 'expira_at' => null]);
            } elseif ($accion === 'completar') {
                if ($r->estado_pago !== Reserva::ESTADO_APROBADO || $h->hora_fin->gt(now())) {
                    throw ValidationException::withMessages(['general' => 'Solo se completan reservas pagadas cuyo horario ya finalizó.']);
                }
                $r->update(['estado_reserva' => 'completada']);
            } else {
                $h->update(['estado' => 'disponible']);
                $r->update(['estado_reserva' => $accion === 'rechazar' ? 'rechazada' : 'cancelada',
                    'motivo' => $datos['motivo'] ?? 'Cancelada por solicitud del usuario', 'expira_at' => null]);
            }
            RegistroReserva::guardar($r, $accion, ['antes' => $antes, 'despues' => ['horario_id' => $r->horario_id, 'estado' => $r->estado_reserva]]);

            return $r;
        }, 3);
    }
}
