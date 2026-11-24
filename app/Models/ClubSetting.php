<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubSetting extends Model
{
    protected $guarded = [];

    protected $casts = ['reglas' => 'array'];

    public static function reglas(): array
    {
        $guardadas = static::find(1)?->reglas ?? [];
        $reglas = array_replace([
            'apertura' => '06:00', 'cierre' => '22:00',
            'horarios_semana' => [
                0 => ['apertura' => '06:00', 'cierre' => '15:00'],
                1 => ['apertura' => '06:00', 'cierre' => '22:00'],
                2 => ['apertura' => '06:00', 'cierre' => '22:00'],
                3 => ['apertura' => '06:00', 'cierre' => '22:00'],
                4 => ['apertura' => '06:00', 'cierre' => '22:00'],
                5 => ['apertura' => '06:00', 'cierre' => '22:00'],
                6 => ['apertura' => '06:00', 'cierre' => '20:00'],
            ],
            'dias' => [0, 1, 2, 3, 4, 5, 6], 'festivos' => [],
            'descanso_inicio' => null, 'descanso_fin' => null,
            'anticipacion_minutos' => 0, 'anticipacion_dias' => 90,
            'cancelacion_horas' => 2, 'margen_minutos' => 0,
            'duracion_minutos' => 60,
            'generacion_automatica' => true, 'dias_publicacion' => 30,
            'tarifa_dia_id' => null, 'tarifa_noche_id' => null,
        ], $guardadas);
        // Las configuraciones anteriores conservan su horario hasta que el club las actualice.
        if ($guardadas && ! isset($guardadas['horarios_semana'])) {
            $reglas['horarios_semana'] = array_fill(0, 7, ['apertura' => $reglas['apertura'], 'cierre' => $reglas['cierre']]);
        }

        return $reglas;
    }

    public static function horarioDelDia(int $dia, ?array $reglas = null): ?array
    {
        $reglas ??= static::reglas();

        return in_array($dia, $reglas['dias']) ? ($reglas['horarios_semana'][$dia] ?? null) : null;
    }
}
