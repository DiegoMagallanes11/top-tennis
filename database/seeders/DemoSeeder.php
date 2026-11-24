<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Cancha;
use App\Models\ClubSetting;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') || User::exists() || Cancha::exists()) {
            throw new \RuntimeException('La demostración requiere una base de datos separada, vacía y fuera de producción.');
        }
        // ===== Usuarios =====
        // 'rol' se asigna explícito porque NO es asignable en masa (seguridad).
        $cliente = User::create([
            'name' => 'Cliente Demo',
            'email' => 'demo@toptennis.local',
            'password' => Hash::make(Str::random(40)),
        ]);
        $cliente->rol = Rol::Cliente;
        $cliente->save();

        // ===== 4 Canchas =====
        $central = Cancha::create([
            'nombre' => 'Cancha Central',
            'tipo_superficie' => 'Arcilla',
            'imagen' => 'Arcilla.jpeg',
            'modalidad' => 'Singles',
            'iluminacion' => true,
            'estado_mantenimiento' => 'operativa',
        ]);

        $norte = Cancha::create([
            'nombre' => 'Cancha Norte',
            'tipo_superficie' => 'Arcilla',
            'imagen' => 'Arcilla.jpeg',
            'modalidad' => 'Dobles',
            'iluminacion' => true,
            'estado_mantenimiento' => 'operativa',
        ]);

        $este = Cancha::create([
            'nombre' => 'Cancha Este',
            'tipo_superficie' => 'Arcilla',
            'imagen' => 'Arcilla.jpeg',
            'modalidad' => 'Ambos',
            'iluminacion' => true,
            'estado_mantenimiento' => 'operativa',
        ]);

        Cancha::create([
            'nombre' => 'Cancha Sur',
            'tipo_superficie' => 'Arcilla',
            'imagen' => 'Arcilla.jpeg',
            'modalidad' => 'Singles',
            'iluminacion' => false,
            'estado_mantenimiento' => 'en_mantenimiento',
            'motivo_mantenimiento' => 'Reparación de superficie',
            'inicio_mantenimiento' => now(),
            'fin_mantenimiento' => now()->addDays(7),
        ]);

        // ===== Tarifas =====
        $tarifaDia = Tarifa::create(['nombre_tarifa' => 'Tarifa Día',   'precio' => 50.00]);
        $tarifaNoche = Tarifa::create(['nombre_tarifa' => 'Tarifa Noche', 'precio' => 60.00]);

        // ===== Horarios de demostración según la atención de cada día =====
        $canchasOperativas = [$central, $norte, $este];
        $hoy = Carbon::today();

        $primerHorario = null;
        $segundoHorario = null;

        for ($dia = 0; $dia < 7; $dia++) {
            $fecha = $hoy->copy()->addDays($dia);
            $horasDia = ClubSetting::horarioDelDia($fecha->dayOfWeek);
            if (! $horasDia) {
                continue;
            }

            foreach ($canchasOperativas as $cancha) {
                for ($hora = (int) substr($horasDia['apertura'], 0, 2); $hora < (int) substr($horasDia['cierre'], 0, 2); $hora++) {
                    $inicio = $fecha->copy()->setTime($hora, 0);
                    $fin = $fecha->copy()->setTime($hora + 1, 0);

                    $tarifa = $hora < 18 ? $tarifaDia : $tarifaNoche;

                    $horario = Horario::create([
                        'cancha_id' => $cancha->id,
                        'tarifa_id' => $tarifa->id,
                        'hora_inicio' => $inicio,
                        'hora_fin' => $fin,
                        'estado' => 'disponible',
                    ]);

                    if ($dia === 1 && $cancha->id === $central->id && $hora === 9 && ! $primerHorario) {
                        $primerHorario = $horario;
                    }
                    if ($dia === 1 && $cancha->id === $central->id && $hora === 15 && ! $segundoHorario) {
                        $segundoHorario = $horario;
                    }
                }
            }
        }

        // ===== Reservas demo =====
        if ($primerHorario) {
            Reserva::create([
                'user_id' => $cliente->id,
                'horario_id' => $primerHorario->id,
                'metodo_pago' => 'Yape',
                'numero_operacion' => '01234567',
                'estado_pago' => Reserva::ESTADO_APROBADO,
                'monto_pagado' => $primerHorario->tarifa->precio,
                'codigo_validacion' => Reserva::generarCodigoValidacion(),
            ]);
            $primerHorario->update(['estado' => 'reservado']);
        }

        if ($segundoHorario) {
            Reserva::create([
                'user_id' => $cliente->id,
                'horario_id' => $segundoHorario->id,
                'metodo_pago' => 'Efectivo',
                'estado_pago' => Reserva::ESTADO_PENDIENTE,
                'monto_pagado' => $segundoHorario->tarifa->precio,
                'expira_at' => $segundoHorario->hora_inicio->copy()->subMinutes(Reserva::MINUTOS_GRACIA),
                'codigo_validacion' => Reserva::generarCodigoValidacion(),
            ]);
            $segundoHorario->update(['estado' => 'reservado']);
        }
    }
}
