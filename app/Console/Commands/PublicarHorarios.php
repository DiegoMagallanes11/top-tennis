<?php

namespace App\Console\Commands;

use App\Models\Cancha;
use App\Services\PublicadorHorarios;
use Illuminate\Console\Command;

class PublicarHorarios extends Command
{
    protected $signature = 'horarios:publicar';

    protected $description = 'Mantiene las próximas fechas publicadas según las reglas del club, sin cambiar reservas.';

    public function handle(PublicadorHorarios $publicador): int
    {
        Cancha::restaurarVencidas();
        $resultado = $publicador->asegurar();
        if ($resultado['estado'] === 'faltan_tarifas') {
            $this->error('Selecciona las tarifas de día y noche en Reglas del club.');

            return self::FAILURE;
        }
        $this->info($resultado['estado'] === 'desactivada' ? 'Publicación automática desactivada.' : 'Horarios al día. Nuevos: '.$resultado['creados']);

        return self::SUCCESS;
    }
}
