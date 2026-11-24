<?php

namespace App\Http\Controllers;

use App\Models\BookingEvent;
use App\Models\BookingNotification;
use App\Models\ClubSetting;
use App\Models\Tarifa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        return view('club.edit', ['reglas' => ClubSetting::reglas(), 'tarifas' => Tarifa::orderBy('nombre_tarifa')->get()]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->esAdmin(), 403);
        $datos = $request->validate([
            'apertura' => ['required', 'date_format:H:i'], 'cierre' => ['required', 'date_format:H:i', 'after:apertura'],
            'horarios_semana' => ['sometimes', 'array', 'size:7'],
            'horarios_semana.*' => ['required', 'array:apertura,cierre'],
            'horarios_semana.*.apertura' => ['required', 'date_format:H:i'],
            'horarios_semana.*.cierre' => ['required', 'date_format:H:i', 'after:horarios_semana.*.apertura'],
            'dias' => ['required', 'array', 'min:1'], 'dias.*' => ['integer', 'between:0,6', 'distinct'],
            'festivos' => ['nullable', 'string', 'max:3000'],
            'descanso_inicio' => ['nullable', 'date_format:H:i', 'after_or_equal:apertura', 'before:cierre', 'required_with:descanso_fin'],
            'descanso_fin' => ['nullable', 'date_format:H:i', 'after:descanso_inicio', 'before_or_equal:cierre', 'required_with:descanso_inicio'],
            'anticipacion_minutos' => ['required', 'integer', 'between:0,10080'], 'anticipacion_dias' => ['required', 'integer', 'between:1,365'],
            'cancelacion_horas' => ['required', 'integer', 'between:0,168'], 'margen_minutos' => ['required', 'integer', 'between:0,120'],
            'duracion_minutos' => ['required', 'integer', 'between:15,180'],
            'generacion_automatica' => ['sometimes', 'boolean'],
            'dias_publicacion' => ['sometimes', 'integer', 'between:1,90'],
            'tarifa_dia_id' => ['nullable', 'integer', Rule::exists('tarifas', 'id')->whereNull('deleted_at')],
            'tarifa_noche_id' => ['nullable', 'integer', Rule::exists('tarifas', 'id')->whereNull('deleted_at')],
        ]);
        foreach (['generacion_automatica', 'dias_publicacion', 'tarifa_dia_id', 'tarifa_noche_id'] as $campo) {
            if (! array_key_exists($campo, $datos)) {
                $datos[$campo] = ClubSetting::reglas()[$campo];
            }
        }
        $datos['horarios_semana'] ??= array_fill(0, 7, ['apertura' => $datos['apertura'], 'cierre' => $datos['cierre']]);
        if (array_keys($datos['horarios_semana']) !== range(0, 6)) {
            return back()->withInput()->withErrors(['horarios_semana' => 'Configura los siete días de domingo a sábado.']);
        }
        $datos['festivos'] = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $datos['festivos'] ?? ''))));
        validator(['fechas' => $datos['festivos']], ['fechas.*' => 'date_format:Y-m-d'])->validate();
        if ($datos['anticipacion_minutos'] >= $datos['anticipacion_dias'] * 1440) {
            return back()->withInput()->withErrors(['anticipacion_minutos' => 'La anticipación mínima debe ser menor que la máxima.']);
        }
        DB::transaction(function () use ($datos) {
            ClubSetting::updateOrCreate(['id' => 1], ['reglas' => $datos]);
            BookingEvent::create(['actor_id' => auth()->id(), 'accion' => 'reglas_actualizadas', 'datos' => $datos]);
        });

        return back()->with('success', 'Reglas guardadas. Se aplican a nuevas reservas; las existentes se conservan.');
    }

    public function auditoria(Request $request)
    {
        abort_unless($request->user()->esAdmin(), 403);
        $eventos = BookingEvent::with('actor', 'reserva')->orderByDesc('id')->paginate(25)->withQueryString();
        $notificaciones = BookingNotification::orderByDesc('id')->paginate(10, ['*'], 'avisos');

        return view('club.auditoria', compact('eventos', 'notificaciones'));
    }
}
