<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\StoreReservaRequest;
use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\GestorReservas;
use App\Services\PublicadorHorarios;
use App\Services\ReglasReserva;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    private function actualizarDisponibilidad(): void
    {
        Cancha::restaurarVencidas();
        Reserva::liberarVencidas();
        app(PublicadorHorarios::class)->asegurar();
    }

    public function disponibles(Request $request)
    {
        $this->actualizarDisponibilidad();
        $horarios = Horario::reservables()->with('cancha', 'tarifa')->orderBy('hora_inicio')->get();
        $horarios = app(ReglasReserva::class)->filtrar($horarios);
        $canchasEnMantenimiento = Cancha::where('estado_mantenimiento', 'en_mantenimiento')->orderBy('nombre')->get();

        return view('reservas.disponibles', compact('horarios', 'canchasEnMantenimiento'));
    }

    public function index(Request $request)
    {
        $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d'], 'buscar' => ['nullable', 'string', 'max:100'],
            'cancha' => ['nullable', 'integer'], 'cliente' => ['nullable', 'integer'],
            'estado' => ['nullable', 'in:pendiente,confirmada,cancelada,rechazada,completada']]);
        $this->actualizarDisponibilidad();
        $esStaff = $request->user()->esStaff();
        $query = Reserva::withTrashed()->with(['horario.cancha', 'horario.tarifa', 'user']);
        if (! $esStaff) {
            $query->where('user_id', $request->user()->id);
        }
        if ($request->filled('fecha')) {
            $query->whereHas('horario', fn ($q) => $q->whereDate('hora_inicio', $request->fecha));
        }
        if ($request->filled('cancha')) {
            $query->whereHas('horario', fn ($q) => $q->where('cancha_id', $request->cancha));
        }
        if ($esStaff && $request->filled('cliente')) {
            $query->where('user_id', $request->cliente);
        }
        if ($request->filled('estado')) {
            $query->where('estado_reserva', $request->estado);
        }
        if (in_array($request->metodo_pago, ['Yape', 'Efectivo', 'Tarjeta'], true)) {
            $query->where('metodo_pago', $request->metodo_pago);
        }
        if ($request->filled('buscar')) {
            $term = '%'.$request->buscar.'%';
            $query->where(fn ($q) => $q->where('codigo_validacion', 'like', $term)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
        }
        $totalIngresos = $esStaff ? (clone $query)->where('pago_demo', false)->where('estado_pago', Reserva::ESTADO_APROBADO)->sum('monto_pagado') : 0;
        $reservas = $query->orderBy('created_at', $request->orden === 'antiguas' ? 'asc' : 'desc')->orderByDesc('id')->paginate(15)->withQueryString();
        $canchas = Cancha::orderBy('nombre')->get();

        return view('reservas.index', compact('reservas', 'totalIngresos', 'esStaff', 'canchas'));
    }

    public function confirmar(Horario $horario)
    {
        $this->actualizarDisponibilidad();
        $horario->refresh()->load('cancha', 'tarifa');
        app(ReglasReserva::class)->validar($horario);

        return view('reservas.confirmar', compact('horario'));
    }

    public function store(StoreReservaRequest $request, GestorReservas $gestor)
    {
        $reserva = $gestor->crear($request->user(), $request->validated());
        if ($reserva->pago_demo && $reserva->estado_pago === 'pendiente') {
            return redirect()->route('reservas.pagoDemo', $reserva);
        }

        return redirect()->route('reservas.ticket', $reserva)->with('success', 'Reserva guardada. El pago queda pendiente de verificación en recepción.');
    }

    public function ticket(Reserva $reserva)
    {
        $this->authorize('view', $reserva);
        $reserva->load('horario.cancha', 'horario.tarifa', 'user', 'eventos.actor');
        try {
            $qrSvg = $reserva->qrSvg();
        } catch (\Throwable $e) {
            Log::warning('No se generó el QR', ['reserva_id' => $reserva->id]);
            $qrSvg = '';
        }

        return view('reservas.ticket', compact('reserva', 'qrSvg'));
    }

    public function descargarTicket(Reserva $reserva)
    {
        $this->authorize('view', $reserva);
        $reserva->load('horario.cancha', 'user');
        try {
            $qrSvg = $reserva->qrSvg(200);

            return Pdf::loadView('reservas.ticket-pdf', compact('reserva', 'qrSvg'))->setPaper('a5')->download('ticket-'.$reserva->codigo_validacion.'.pdf');
        } catch (\Throwable $e) {
            Log::error('Error al generar PDF', ['reserva_id' => $reserva->id]);

            return back()->withErrors(['general' => 'No se pudo generar el PDF. Puedes consultar el ticket en pantalla.']);
        }
    }

    public function cancelar(Reserva $reserva, GestorReservas $gestor)
    {
        $this->authorize('update', $reserva);
        $gestor->cambiar($reserva, 'cancelar');

        return redirect()->route('reservas.index')->with('success', 'Reserva cancelada. El horario está disponible. Si ya pagaste, contacta al club para gestionar el saldo.');
    }

    public function confirmarPago(Reserva $reserva, GestorReservas $gestor)
    {
        $this->authorize('manage', $reserva);
        $gestor->cambiar($reserva, 'confirmar');

        return back()->with('success', 'Pago verificado y reserva confirmada.');
    }

    public function gestionar(Request $request, Reserva $reserva, GestorReservas $gestor)
    {
        $this->authorize('manage', $reserva);
        $datos = $request->validate(['accion' => ['required', 'in:rechazar,completar'], 'motivo' => ['required_if:accion,rechazar', 'nullable', 'string', 'max:500']]);
        $gestor->cambiar($reserva, $datos['accion'], $datos);

        return back()->with('success', 'Estado de la reserva actualizado.');
    }

    public function edit(Reserva $reserva)
    {
        $this->authorize('update', $reserva);
        $this->actualizarDisponibilidad();
        $horarios = Horario::reservables()->with('cancha', 'tarifa')->orderBy('hora_inicio')->get();
        $horarios = app(ReglasReserva::class)->filtrar($horarios, $reserva->estado_pago === Reserva::ESTADO_APROBADO);

        return view('reservas.edit', compact('reserva', 'horarios'));
    }

    public function update(Request $request, Reserva $reserva, GestorReservas $gestor)
    {
        $this->authorize('update', $reserva);
        $datos = $request->validate(['horario_id' => ['required', 'integer', 'exists:horarios,id']]);
        $gestor->cambiar($reserva, 'modificar', $datos);

        return redirect()->route('reservas.ticket', $reserva)->with('success', 'Cambio de horario guardado.');
    }

    public function crearManual()
    {
        $this->actualizarDisponibilidad();
        $horarios = Horario::reservables()->with('cancha', 'tarifa')->orderBy('hora_inicio')->get();
        $horarios = app(ReglasReserva::class)->filtrar($horarios, true);
        $canchasJson = $horarios->pluck('cancha')->unique('id')->mapWithKeys(fn ($c) => [$c->id => ['id' => $c->id, 'nombre' => $c->nombre, 'tipo' => $c->tipo_superficie, 'imagen' => $c->imagenUrl()]]);
        $horariosJson = $horarios->groupBy(fn ($h) => $h->hora_inicio->format('Y-m-d'))->map(fn ($day) => $day->groupBy('cancha_id')->map(fn ($slots) => $slots->map(fn ($h) => ['id' => $h->id, 'hora' => $h->hora_inicio->format('H:i'), 'precio' => (float) $h->tarifa->precio, 'tarifa' => $h->tarifa->nombre_tarifa])->values()));
        $clientes = User::where('rol', Rol::Cliente)->where('activo', true)->orderBy('name')->get();

        return view('reservas.crear-manual', compact('canchasJson', 'horariosJson', 'clientes'));
    }

    public function storeManual(Request $request, GestorReservas $gestor)
    {
        abort_unless($request->user()->esStaff(), 403);
        $datos = $request->validate([
            'horario_id' => ['required', 'integer', 'exists:horarios,id'], 'metodo_pago' => ['required', 'in:Yape,Efectivo'],
            'clave_solicitud' => ['nullable', 'uuid'], 'numero_operacion' => ['nullable', 'string', 'max:50', 'unique:reservas,numero_operacion'],
            'modo_cliente' => ['required', 'in:existente,nuevo'],
            'user_id' => ['required_if:modo_cliente,existente', 'nullable', Rule::exists('users', 'id')->where('rol', 'cliente')->where('activo', true)->whereNull('deleted_at')],
            'nuevo_nombre' => ['required_if:modo_cliente,nuevo', 'nullable', 'string', 'max:255'],
            'nuevo_telefono' => ['nullable', 'string', 'regex:/^[+0-9 ()-]{6,20}$/'],
        ]);
        $reserva = DB::transaction(function () use ($datos, $gestor) {
            $cliente = $datos['modo_cliente'] === 'nuevo'
                ? User::create(['name' => $datos['nuevo_nombre'], 'telefono' => $datos['nuevo_telefono'] ?? null, 'email' => 'cliente-'.Str::uuid().'@toptennis.local', 'password' => bcrypt('cliente-inicial')])
                : User::findOrFail($datos['user_id']);

            return $gestor->crear($cliente, $datos, true);
        });

        return redirect()->route('reservas.index')->with('success', 'Reserva '.$reserva->codigo_validacion.' guardada con el cobro registrado en caja.');
    }
}
