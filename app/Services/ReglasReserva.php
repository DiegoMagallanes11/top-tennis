<?php

namespace App\Services;

use App\Models\ClubSetting;
use App\Models\Horario;
use App\Models\Reserva;
use Illuminate\Validation\ValidationException;

class ReglasReserva
{
    public function filtrar($horarios, bool $pagoYaVerificado = false)
    {
        $ocupados = Horario::where('estado', 'reservado')->get();
        $reglas = ClubSetting::reglas();
        $margen = $reglas['margen_minutos'];

        return $horarios->filter(function ($h) use ($ocupados, $margen, $pagoYaVerificado, $reglas) {
            if (! $this->permite($h, $reglas) || (! $pagoYaVerificado && $h->hora_inicio->lte(now()->addMinutes(Reserva::MINUTOS_GRACIA)))) {
                return false;
            }

            return ! $ocupados->contains(fn ($o) => $o->cancha_id === $h->cancha_id
                && $o->hora_inicio->lt($h->hora_fin->copy()->addMinutes($margen))
                && $o->hora_fin->gt($h->hora_inicio->copy()->subMinutes($margen)));
        })->values();
    }

    public function permite(Horario $horario, ?array $reglas = null): bool
    {
        $r = $reglas ?? ClubSetting::reglas();
        $inicio = $horario->hora_inicio;
        $fin = $horario->hora_fin;
        if (! $inicio || ! $fin) {
            return false;
        }
        $horas = ClubSetting::horarioDelDia($inicio->dayOfWeek, $r);
        if (! $horas || $inicio->lte(now()->addMinutes($r['anticipacion_minutos']))
            || $inicio->gt(now()->addDays($r['anticipacion_dias']))
            || ! in_array($inicio->dayOfWeek, $r['dias'])
            || in_array($inicio->toDateString(), $r['festivos'])
            || $inicio->format('H:i') < $horas['apertura']
            || $fin->gt($inicio->copy()->setTimeFromTimeString($horas['cierre']))
            || $fin->lte($inicio)) {
            return false;
        }
        if ($r['descanso_inicio'] && $r['descanso_fin']
            && $inicio->lt($inicio->copy()->setTimeFromTimeString($r['descanso_fin']))
            && $fin->gt($inicio->copy()->setTimeFromTimeString($r['descanso_inicio']))) {
            return false;
        }

        return true;
    }

    public function validar(Horario $horario, ?int $ignorarHorario = null): void
    {
        if ($horario->estado !== 'disponible' || ! $horario->cancha?->estaOperativa() || ! $horario->tarifa || ! $this->permite($horario)) {
            throw ValidationException::withMessages(['horario_id' => 'El horario no está disponible o no cumple las reglas del club. Elige otro.']);
        }
        $margen = ClubSetting::reglas()['margen_minutos'];
        $ocupado = Horario::where('cancha_id', $horario->cancha_id)->where('estado', 'reservado')
            ->when($ignorarHorario, fn ($q) => $q->where('id', '!=', $ignorarHorario))
            ->where('hora_inicio', '<', $horario->hora_fin->copy()->addMinutes($margen))
            ->where('hora_fin', '>', $horario->hora_inicio->copy()->subMinutes($margen))->exists();
        if ($ocupado) {
            throw ValidationException::withMessages(['horario_id' => 'La cancha está ocupada durante ese horario o su margen de preparación.']);
        }
    }
}
