<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OwnReservation
{
    public function handle(Request $request, Closure $next)
    {
        $reservaId = $request->route('reserva');
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admins y recepcionistas pueden ver todas
        if ($user->esStaff()) {
            return $next($request);
        }

        // Clientes solo pueden acceder a sus propias reservas
        if ($reservaId) {
            // El enlace de modelos puede haber resuelto ya el parámetro.
            // find() con un modelo lo trata como una lista y devuelve Collection.
            $reserva = $reservaId instanceof \App\Models\Reserva
                ? $reservaId
                : \App\Models\Reserva::withTrashed()->find($reservaId);
            if (!$reserva || $reserva->user_id !== $user->id) {
                abort(403, 'No tienes permiso para acceder a esta reserva.');
            }
        }

        return $next($request);
    }
}
