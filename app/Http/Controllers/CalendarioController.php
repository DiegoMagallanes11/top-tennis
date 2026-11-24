<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Services\PublicadorHorarios;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// CALENDARIO VISUAL DEL ADMIN (estilo Google Calendar).
// index() muestra la vista con FullCalendar; datos() entrega los horarios en JSON.
// Colores: verde = libre, rojo = reservado y pagado, naranja = reservado pendiente,
//          gris = cancha en mantenimiento.
class CalendarioController extends Controller
{
    public function index(): View
    {
        $canchas = Cancha::orderBy('nombre')->get(['id', 'nombre']);

        return view('calendario.index', compact('canchas'));
    }

    /**
     * Devuelve los horarios del rango solicitado como eventos para FullCalendar.
     */
    public function datos(Request $request): JsonResponse
    {
        $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date', 'after:start', 'before_or_equal:'.now()->addYears(2)->toDateString()], 'cancha' => ['nullable', 'integer']]);
        abort_if(Carbon::parse($request->start)->diffInDays(Carbon::parse($request->end)) > 93, 422, 'Consulta como máximo 93 días.');
        Cancha::restaurarVencidas();
        Reserva::liberarVencidas();
        app(PublicadorHorarios::class)->asegurar();
        $inicio = $request->query('start');
        $fin = $request->query('end');
        $cancha = $request->query('cancha');

        $query = Horario::with(['cancha', 'reserva.user'])->whereHas('cancha');

        if ($inicio) {
            $query->where('hora_inicio', '>=', $inicio);
        }
        if ($fin) {
            $query->where('hora_inicio', '<=', $fin);
        }
        if ($cancha) {
            $query->where('cancha_id', $cancha);
        }

        $eventos = $query->orderBy('hora_inicio')->get()->map(function (Horario $h) {
            $nombreCancha = optional($h->cancha)->nombre ?? 'Cancha';
            $enMant = optional($h->cancha)->estado_mantenimiento === 'en_mantenimiento';
            $reservado = $h->estado === 'reservado';

            if ($enMant) {
                $color = '#9ca3af'; // gris
                $titulo = $nombreCancha.' · Mantenimiento';
            } elseif ($reservado) {
                $reserva = $h->reserva; // hasOne (excluye canceladas por SoftDeletes)
                $cliente = optional(optional($reserva)->user)->name ?? 'Reservado';
                $pagado = optional($reserva)->estado_pago === Reserva::ESTADO_APROBADO;
                $color = $pagado ? '#dc2626' : '#d97706'; // rojo pagado / naranja pendiente
                $titulo = $nombreCancha.' · '.$cliente;
            } else {
                $color = '#16a34a'; // verde libre
                $titulo = $nombreCancha.' · Libre';
            }

            return [
                'id' => $h->id,
                'title' => $titulo,
                'start' => optional($h->hora_inicio)->toIso8601String(),
                'end' => optional($h->hora_fin)->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'estado' => $enMant ? 'mantenimiento' : $h->estado,
                    'cancha' => $nombreCancha,
                    'ticket' => $reservado && $h->reserva ? route('reservas.ticket', $h->reserva) : null,
                ],
            ];
        });

        return response()->json($eventos);
    }
}
