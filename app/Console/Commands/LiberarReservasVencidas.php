<?php

namespace App\Console\Commands;

use App\Models\Cancha;
use App\Models\Reserva;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// COMANDO ARTISAN: SE EJECUTA CADA MINUTO VIA SCHEDULER (routes/console.php,
// everyMinute + withoutOverlapping). LA LOGICA REAL VIVE EN los modelos
// (Reserva::liberarVencidas y Cancha::restaurarVencidas) PARA NO DUPLICARLA
// CON EL MODO "LAZY" QUE CORRE AL CARGAR LAS VISTAS.
// PROBAR A MANO CON: php artisan reservas:liberar-vencidas
#[Signature('reservas:liberar-vencidas')]
#[Description('Anula las reservas en Efectivo vencidas (no-show) y restaura las canchas cuyo mantenimiento terminó.')]
class LiberarReservasVencidas extends Command
{
    public function handle(): int
    {
        // Restaura automáticamente las canchas cuyo mantenimiento ya venció,
        // sin depender de que alguien abra una página.
        Cancha::restaurarVencidas();

        $liberadas = Reserva::liberarVencidas();

        $this->info("Reservas vencidas liberadas: {$liberadas}. Canchas restauradas si correspondía.");

        return self::SUCCESS;
    }
}
