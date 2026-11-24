<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Services\PublicadorHorarios;
use App\Services\ReglasReserva;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// DASHBOARD: muestra estadísticas distintas según el rol.
// Staff (admin/recepcionista): KPIs del negocio, ingresos, métricas y estado de canchas.
// Cliente: horarios disponibles y sus reservas.
class DashboardController extends Controller
{
    public function index(): View
    {
        Cancha::restaurarVencidas();
        Reserva::liberarVencidas();
        $publicacion = app(PublicadorHorarios::class)->asegurar();
        $user = auth()->user();
        $esStaff = $user->esStaff();
        $cupos = app(ReglasReserva::class)->filtrar(Horario::reservables()->with('cancha', 'tarifa')->get());

        if (! $esStaff) {
            $stats = [
                'disponibles' => $cupos->filter(fn ($h) => $h->hora_inicio->isToday())->count(),
                'disponibles_hoy' => $cupos->filter(fn ($h) => $h->hora_inicio->isToday())->count(),
                'mis_reservas' => $user->reservas()->withTrashed()->count(),
            ];
            $proximaReserva = $user->reservas()->with('horario.cancha')
                ->whereIn('estado_reserva', ['pendiente', 'confirmada'])
                ->whereHas('horario', fn ($q) => $q->where('hora_fin', '>', now()))
                ->orderBy(Horario::select('hora_inicio')->whereColumn('horarios.id', 'reservas.horario_id')->limit(1))
                ->first();

            return view('dashboard', compact('esStaff', 'stats', 'proximaReserva'));
        }

        $stats = [
            'cancelaciones' => Reserva::withTrashed()->whereIn('estado_reserva', ['cancelada', 'rechazada'])->count(),
            'completadas' => Reserva::where('estado_reserva', 'completada')->count(),
            'canchas' => Cancha::count(),
            'canchas_operativas' => Cancha::where('estado_mantenimiento', 'operativa')->count(),
            'tarifas' => Tarifa::count(),
            'horarios_disp' => $cupos->count(),
            'reservas' => Reserva::count(),
            // Suma monto_pagado (precio congelado al reservar), nunca la tarifa actual.
            'ingresos' => (float) Reserva::where('pago_demo', false)->where('estado_pago', 'aprobado')->sum('monto_pagado'),
        ];

        $canchas = Cancha::orderBy('nombre')->get();
        $slotsSemana = Horario::whereBetween('hora_inicio', [now()->startOfWeek(), now()->endOfWeek()]);
        $totalSlots = (clone $slotsSemana)->count();
        $stats['ocupacion'] = $totalSlots ? round((clone $slotsSemana)->where('estado', 'reservado')->count() * 100 / $totalSlots, 1) : 0;
        $metrics = $this->metricas();
        $agendaQuery = Reserva::withTrashed()->with('user', 'horario.cancha')
            ->whereHas('horario', fn ($q) => $q->whereDate('hora_inicio', today()))
            ->orderBy(Horario::select('hora_inicio')->whereColumn('horarios.id', 'reservas.horario_id')->limit(1));
        $totalAgenda = (clone $agendaQuery)->count();
        $agendaHoy = $agendaQuery->limit(30)->get();
        $pendientesQuery = Reserva::with('user', 'horario.cancha')
            ->where('pago_demo', false)
            ->where('estado_reserva', 'pendiente')->where('estado_pago', 'pendiente')
            ->where('expira_at', '>', now())->orderBy('expira_at');
        $totalPendientes = (clone $pendientesQuery)->count();
        $pagosPendientes = $pendientesQuery->limit(8)->get();
        $ultimoHorarioPublicado = Horario::where('hora_inicio', '>', now())
            ->whereHas('cancha', fn ($q) => $q->where('estado_mantenimiento', 'operativa'))
            ->max('hora_inicio');

        return view('dashboard', compact('esStaff', 'stats', 'canchas', 'metrics', 'agendaHoy', 'totalAgenda', 'pagosPendientes', 'totalPendientes', 'ultimoHorarioPublicado', 'publicacion'));
    }

    /**
     * Métricas de negocio para el dashboard del staff.
     */
    private function metricas(): array
    {
        $aprobado = fn () => Reserva::where('pago_demo', false)->where('estado_pago', Reserva::ESTADO_APROBADO);

        // Ingresos por periodo (según cuándo se registró la reserva pagada).
        $ingresosHoy = (float) $aprobado()->whereDate('created_at', today())->sum('monto_pagado');
        $ingresosSemana = (float) $aprobado()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('monto_pagado');
        $ingresosMes = (float) $aprobado()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('monto_pagado');

        // Las cancelaciones no representan ausencias: aún no existe un registro de no-show.
        $canceladas = Reserva::withTrashed()->whereIn('estado_reserva', ['cancelada', 'rechazada'])->count();
        $total = Reserva::withTrashed()->count();
        $tasaCancelacion = $total > 0 ? round($canceladas * 100 / $total, 1) : 0.0;

        // Reservas activas: no anuladas y cuyo horario aún no comienza.
        $reservasActivas = Reserva::whereIn('estado_reserva', ['pendiente', 'confirmada'])
            ->whereHas('horario', fn ($q) => $q->where('hora_inicio', '>', now()))
            ->count();

        // Serie de los últimos 7 días para el gráfico de ingresos.
        $serieDias = collect(range(6, 0))->map(function ($i) {
            $d = today()->subDays($i);

            return [
                'dia' => ucfirst($d->locale('es')->isoFormat('ddd')),
                'total' => (float) Reserva::where('pago_demo', false)->where('estado_pago', Reserva::ESTADO_APROBADO)
                    ->whereDate('created_at', $d)->sum('monto_pagado'),
            ];
        })->values();

        // Canchas más utilizadas.
        $topCanchas = Reserva::query()
            ->join('horarios', 'horarios.id', '=', 'reservas.horario_id')
            ->join('canchas', 'canchas.id', '=', 'horarios.cancha_id')
            ->selectRaw('canchas.nombre as nombre, COUNT(*) as total')
            ->groupBy('canchas.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Horarios (por hora del día) más demandados.
        $topHorarios = Reserva::query()
            ->join('horarios', 'horarios.id', '=', 'reservas.horario_id')
            ->selectRaw((DB::getDriverName() === 'sqlite' ? "CAST(strftime('%H', horarios.hora_inicio) AS INTEGER)" : 'HOUR(horarios.hora_inicio)').' as hora, COUNT(*) as total')
            ->groupBy('hora')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return compact(
            'ingresosHoy', 'ingresosSemana', 'ingresosMes',
            'tasaCancelacion', 'reservasActivas', 'serieDias',
            'topCanchas', 'topHorarios'
        );
    }
}
