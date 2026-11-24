<?php

namespace App\Console\Commands;

use App\Models\BookingEvent;
use App\Models\Cancha;
use App\Models\ClubSetting;
use App\Models\Reserva;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AlinearDatosClub extends Command
{
    protected $signature = 'club:alinear-datos {--aplicar : Guarda la corrección autorizada después de crear un respaldo}';

    protected $description = 'Revisa o aplica arcilla y los horarios de atención proporcionados por el club, sin cancelar reservas.';

    public function handle(): int
    {
        $horarios = array_fill(0, 7, ['apertura' => '06:00', 'cierre' => '22:00']);
        $horarios[0]['cierre'] = '15:00';
        $horarios[6]['cierre'] = '20:00';
        $this->info('Superficies: arcilla. Lun–Vie 06:00–22:00 · Sáb 06:00–20:00 · Dom 06:00–15:00.');
        $fuera = Reserva::whereIn('estado_reserva', ['pendiente', 'confirmada'])
            ->with('horario')->whereHas('horario', fn ($q) => $q->where('hora_fin', '>', now()))
            ->get()->filter(function ($r) use ($horarios) {
                $h = $r->horario;

                return $h->hora_inicio->format('H:i') < $horarios[$h->hora_inicio->dayOfWeek]['apertura']
                    || $h->hora_fin->gt($h->hora_inicio->copy()->setTimeFromTimeString($horarios[$h->hora_inicio->dayOfWeek]['cierre']));
            });
        $this->line('Reservas activas fuera del nuevo horario: '.$fuera->count().'. Se conservarán para revisión del club.');
        if (! $this->option('aplicar')) {
            $this->line('Solo revisión: no se cambió ningún dato.');

            return self::SUCCESS;
        }
        DB::transaction(function () use ($horarios, $fuera) {
            $antes = ClubSetting::reglas();
            $reglas = $antes;
            $reglas['horarios_semana'] = $horarios;
            ClubSetting::updateOrCreate(['id' => 1], ['reglas' => $reglas]);
            $cambios = [];
            foreach (Cancha::orderBy('id')->lockForUpdate()->get() as $cancha) {
                $original = ['superficie' => $cancha->tipo_superficie, 'imagen' => $cancha->imagen];
                $cancha->tipo_superficie = 'Arcilla';
                if (in_array($cancha->imagen, ['Dura.jpeg', 'Cesped.jpeg', 'CespedArtificial.jpeg'], true)) {
                    $cancha->imagen = 'Arcilla.jpeg';
                }
                if ($cancha->isDirty()) {
                    $cancha->save();
                    $cambios[] = ['cancha_id' => $cancha->id, 'antes' => $original,
                        'despues' => ['superficie' => $cancha->tipo_superficie, 'imagen' => $cancha->imagen]];
                }
            }
            if ($antes !== $reglas || $cambios) {
                BookingEvent::create(['accion' => 'datos_club_alineados', 'datos' => [
                    'motivo' => 'Superficies y horario real confirmados por el responsable del proyecto',
                    'canchas' => $cambios, 'horario_anterior' => $antes['horarios_semana'],
                    'horario_nuevo' => $horarios, 'reservas_a_revisar' => $fuera->pluck('id')->all(),
                ]]);
            }
        });
        $this->info('Datos del club actualizados. Usuarios, tarifas, horarios publicados y reservas se conservaron.');

        return self::SUCCESS;
    }
}
