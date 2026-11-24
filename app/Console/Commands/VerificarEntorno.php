<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VerificarEntorno extends Command
{
    protected $signature = 'club:verificar-entorno';

    protected $description = 'Comprueba configuración esencial sin mostrar secretos ni certificar producción.';

    public function handle(): int
    {
        $fallos = [];
        if (! config('app.key')) {
            $fallos[] = 'Falta APP_KEY.';
        }
        if (! Schema::hasColumn('reservas', 'estado_reserva')) {
            $fallos[] = 'Falta aplicar la migración del flujo de reservas.';
        }
        if (! Schema::hasColumn('reservas', 'pago_demo')) {
            $fallos[] = 'Falta aplicar la migración de pagos de demostración.';
        }
        if (! is_file(public_path('build/manifest.json'))) {
            $fallos[] = 'Falta compilar los estilos y scripts.';
        }
        if (config('pagos.driver') !== 'manual') {
            $fallos[] = 'El proveedor de pago configurado no está implementado. Usa manual mientras integras y pruebas una pasarela real.';
        }
        if (config('mail.notifications_enabled') && in_array(config('mail.default'), ['log', 'array'])) {
            $fallos[] = 'Los avisos están activados sin un transporte de correo real.';
        }
        if (app()->environment('production')) {
            if (config('app.debug')) {
                $fallos[] = 'APP_DEBUG debe ser false.';
            }
            if (! str_starts_with(config('app.url'), 'https://')) {
                $fallos[] = 'APP_URL debe usar HTTPS.';
            }
            if (! config('session.secure')) {
                $fallos[] = 'SESSION_SECURE_COOKIE debe ser true.';
            }
            if (! config('session.encrypt')) {
                $fallos[] = 'SESSION_ENCRYPT debe ser true.';
            }
            if (config('database.connections.'.config('database.default').'.username') === 'root') {
                $fallos[] = 'Usa una cuenta limitada de base de datos, no root.';
            }
            if (in_array(config('mail.default'), ['log', 'array'])) {
                $fallos[] = 'Falta configurar y probar el correo de recuperación de contraseña.';
            }
        }
        foreach ($fallos as $fallo) {
            $this->error($fallo);
        }
        $this->warn('Este chequeo no sustituye pruebas de MySQL, pagos, correo, HTTPS y restauración de copias.');

        return $fallos ? self::FAILURE : self::SUCCESS;
    }
}
