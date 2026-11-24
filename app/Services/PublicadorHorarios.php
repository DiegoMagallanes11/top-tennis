<?php

namespace App\Services;

use App\Models\BookingEvent;
use App\Models\Cancha;
use App\Models\ClubSetting;
use App\Models\Horario;
use App\Models\Tarifa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicadorHorarios
{
    /** Completa fechas futuras; nunca cambia horarios existentes ni reservas. */
    public function asegurar(): array
    {
        $reglas = ClubSetting::reglas();
        if (! $reglas['generacion_automatica']) {
            return ['estado' => 'desactivada', 'creados' => 0];
        }
        $dia = $this->tarifa($reglas['tarifa_dia_id'], 'Tarifa Día');
        $noche = $this->tarifa($reglas['tarifa_noche_id'], 'Tarifa Noche');
        if (! $dia || ! $noche) {
            return ['estado' => 'faltan_tarifas', 'creados' => 0];
        }
        $canchas = Cancha::where('estado_mantenimiento', 'operativa')->orderBy('id')->get();
        $dias = min((int) $reglas['dias_publicacion'], (int) $reglas['anticipacion_dias']);
        $hasta = today()->addDays($dias - 1)->endOfDay();
        // Cambiar reglas, tarifas o canchas invalida la revisión; cada nuevo día también.
        $clave = 'horarios:publicacion:'.hash('sha256', json_encode([
            today()->toDateString(), $reglas, $canchas->map->only(['id', 'updated_at']),
            $dia->only(['id', 'precio', 'updated_at']), $noche->only(['id', 'precio', 'updated_at']),
        ]));
        if (Cache::has($clave)) {
            return ['estado' => 'vigente', 'creados' => 0];
        }
        $creados = DB::transaction(function () use ($canchas, $reglas, $dia, $noche, $dias, $hasta) {
            $creados = 0;
            foreach ($canchas as $cancha) {
                // El generador manual y las reservas bloquean la misma cancha.
                $cancha = Cancha::whereKey($cancha->id)->lockForUpdate()->first();
                if (! $cancha?->estaOperativa()) {
                    continue;
                }
                // También respetar bajas lógicas: un horario retirado no debe reaparecer.
                $existentes = Horario::withTrashed()->where('cancha_id', $cancha->id)
                    ->where('hora_inicio', '<=', $hasta)->where('hora_fin', '>', today())->get()
                    ->map(fn ($h) => [$h->hora_inicio->timestamp, $h->hora_fin->timestamp])->all();
                $nuevos = [];
                for ($i = 0; $i < $dias; $i++) {
                    $fecha = today()->addDays($i);
                    $horas = ClubSetting::horarioDelDia($fecha->dayOfWeek, $reglas);
                    if (! $horas) {
                        continue;
                    }
                    $cierre = $fecha->copy()->setTimeFromTimeString($horas['cierre']);
                    for ($inicio = $fecha->copy()->setTimeFromTimeString($horas['apertura']); $inicio->copy()->addMinutes($reglas['duracion_minutos'])->lte($cierre); $inicio->addMinutes($reglas['duracion_minutos'])) {
                        $fin = $inicio->copy()->addMinutes($reglas['duracion_minutos']);
                        $slot = new Horario(['hora_inicio' => $inicio->copy(), 'hora_fin' => $fin]);
                        if (! app(ReglasReserva::class)->permite($slot, $reglas)) {
                            continue;
                        }
                        $cruce = false;
                        foreach ($existentes as [$desde, $hastaExistente]) {
                            if ($desde < $fin->timestamp && $hastaExistente > $inicio->timestamp) {
                                $cruce = true;
                                break;
                            }
                        }
                        if ($cruce) {
                            continue;
                        }
                        $nuevos[] = [
                            'cancha_id' => $cancha->id, 'tarifa_id' => ($inicio->hour < 18 ? $dia : $noche)->id,
                            'hora_inicio' => $inicio->toDateTimeString(), 'hora_fin' => $fin->toDateTimeString(),
                            'estado' => 'disponible', 'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
                        ];
                    }
                }
                foreach (array_chunk($nuevos, 500) as $lote) {
                    Horario::insert($lote);
                }
                $creados += count($nuevos);
            }
            if ($creados) {
                BookingEvent::create(['accion' => 'horarios_publicados_automaticamente', 'datos' => [
                    'desde' => today()->toDateString(), 'hasta' => $hasta->toDateString(), 'creados' => $creados,
                    'tarifa_dia_id' => $dia->id, 'tarifa_noche_id' => $noche->id,
                ]]);
            }

            return $creados;
        }, 3);
        DB::afterCommit(fn () => Cache::put($clave, true, now()->endOfDay()));

        return ['estado' => 'vigente', 'creados' => $creados];
    }

    private function tarifa(?int $id, string $nombre): ?Tarifa
    {
        if ($id !== null) {
            return Tarifa::find($id);
        }
        $coincidencias = Tarifa::where('nombre_tarifa', $nombre)->limit(2)->get();

        return $coincidencias->count() === 1 ? $coincidencias->first() : null;
    }
}
