<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EnsureBookingWorkflowIsReady
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->routeIs('logout', 'profile.*', 'password.*', 'verification.*')
            && (! Schema::hasColumn('reservas', 'estado_reserva') || ! Schema::hasColumn('reservas', 'pago_demo'))) {
            $message = 'Estamos actualizando el sistema de reservas. Intenta de nuevo cuando el club termine la actualización.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : response()->view('errors.503', [], 503);
        }

        return $next($request);
    }
}
