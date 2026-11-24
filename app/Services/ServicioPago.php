<?php

namespace App\Services;

use App\Models\Reserva;

/**
 * Los pagos declarados por el cliente requieren comprobación en recepción.
 * Todavía no hay integración con una pasarela ni verificación automática.
 */
class ServicioPago
{
    public static function demoDisponible(): bool
    {
        return config('pagos.demo_enabled') && app()->environment('local', 'testing');
    }

    public static function metodosOnline(): array
    {
        return self::demoDisponible() ? ['Yape', 'Tarjeta'] : (config('pagos.yape_enabled') ? ['Yape'] : []);
    }

    public function estadoInicial(string $metodoPago): string
    {
        return Reserva::ESTADO_PENDIENTE;
    }

    public function verificarPago(string $metodoPago, ?string $numeroOperacion): bool
    {
        // Un dato enviado por el navegador no prueba que el club recibió dinero.
        return false;
    }
}
