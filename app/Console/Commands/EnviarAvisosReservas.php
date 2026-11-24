<?php

namespace App\Console\Commands;

use App\Models\BookingNotification;
use App\Models\Reserva;
use App\Services\RegistroReserva;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarAvisosReservas extends Command
{
    protected $signature = 'reservas:enviar-avisos';

    protected $description = 'Envía avisos configurados y reintenta los fallidos (máximo 3 intentos).';

    public function handle(): int
    {
        if (! config('mail.notifications_enabled') || in_array(config('mail.default'), ['log', 'array'])) {
            $this->warn('Correo externo sin configurar. No se enviaron avisos.');

            return self::SUCCESS;
        }
        // El recordatorio no se genera para reservas anuladas ni pagos pendientes.
        $horas = (int) config('mail.reminder_hours', 24);
        if ($horas > 0) {
            Reserva::where('pago_demo', false)->where('estado_reserva', 'confirmada')->whereHas('horario', fn ($q) => $q->whereBetween('hora_inicio', [now(), now()->addHours($horas)]))
                ->each(function ($reserva) {
                    DB::transaction(function () use ($reserva) {
                        $r = Reserva::whereKey($reserva->id)->lockForUpdate()->first();
                        if (! BookingNotification::where('reserva_id', $r->id)->where('evento', 'recordatorio')->exists()) {
                            RegistroReserva::guardar($r, 'recordatorio');
                        }
                    });
                });
        }
        BookingNotification::whereIn('estado', ['pendiente', 'error'])->where('intentos', '<', 3)->orderBy('id')->limit(50)->get()->each(function ($aviso) {
            // Reserva del envío persistente: dos procesos no envían el mismo aviso.
            $tomado = BookingNotification::whereKey($aviso->id)->whereIn('estado', ['pendiente', 'error'])
                ->update(['estado' => 'enviando', 'intentos' => $aviso->intentos + 1]);
            if (! $tomado) {
                return;
            }
            try {
                Mail::send('emails.reserva-evento', ['aviso' => $aviso], function ($message) use ($aviso) {
                    $message->to($aviso->destinatario)->subject('Top Tennis · '.$aviso->evento.' · '.$aviso->datos['codigo']);
                });
                $aviso->update(['estado' => 'enviado', 'enviado_at' => now(), 'ultimo_error' => null]);
            } catch (\Throwable $e) {
                $aviso->update(['estado' => 'error', 'ultimo_error' => 'No se pudo entregar al servidor de correo.']);
                Log::warning('Fallo de entrega de aviso', ['aviso_id' => $aviso->id, 'tipo' => get_class($e)]);
            }
        });
        $this->info('Avisos procesados. Consulta el registro para ver el estado real de cada envío.');

        return self::SUCCESS;
    }
}
