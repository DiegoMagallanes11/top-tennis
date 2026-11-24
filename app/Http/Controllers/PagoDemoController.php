<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\RegistroReserva;
use App\Services\ServicioPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoDemoController extends Controller
{
    private function comprobar(Reserva $reserva): void
    {
        abort_unless(ServicioPago::demoDisponible(), 404);
        abort_unless($reserva->pago_demo && $reserva->user_id === auth()->id() && ! auth()->user()->esStaff(), 403);
    }

    public function mostrar(Reserva $reserva)
    {
        $this->comprobar($reserva);
        if (! $reserva->activa() || $reserva->estaVencida() || $reserva->estado_pago === 'aprobado') {
            return redirect()->route('reservas.ticket', $reserva);
        }

        return view('reservas.pago-demo', ['reserva' => $reserva->load('horario.cancha')]);
    }

    public function simular(Request $request, Reserva $reserva)
    {
        $this->comprobar($reserva);
        $datos = $request->validate(['resultado' => ['required', 'in:aprobado,rechazado']]);
        DB::transaction(function () use ($reserva, $datos) {
            $r = Reserva::whereKey($reserva->id)->lockForUpdate()->firstOrFail();
            $this->comprobar($r);
            if ($r->estado_pago === 'aprobado' && $r->estado_reserva === 'confirmada') {
                return;
            }
            if (! $r->activa() || $r->estaVencida()) {
                throw ValidationException::withMessages(['general' => 'La reserva venció o ya no está activa. Elige otro horario.']);
            }
            if ($datos['resultado'] === 'aprobado') {
                $r->update(['estado_pago' => 'aprobado', 'estado_reserva' => 'confirmada', 'expira_at' => null]);
            }
            RegistroReserva::guardar($r, 'pago_demo_'.$datos['resultado'], ['simulado' => true, 'importe' => $r->monto_pagado]);
        }, 3);

        return $datos['resultado'] === 'aprobado'
            ? redirect()->route('reservas.ticket', $reserva)->with('success', 'Pago simulado aprobado. No se realizó ningún cobro real.')
            : back()->withErrors(['general' => 'Pago simulado rechazado. Puedes reintentarlo antes de que venza la reserva.']);
    }
}
