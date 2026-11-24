<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// VALIDACIÓN DE TICKETS POR QR (control de acceso).
// mostrar(): página pública que se abre al escanear el QR; muestra si la reserva es válida.
// marcarUsada(): el admin marca el ingreso para que el mismo QR no sirva dos veces.
class ValidacionController extends Controller
{
    public function mostrar(string $token)
    {
        $reserva = Reserva::with(['horario.cancha', 'user'])
            ->where('token_validacion', $token)
            ->first();

        // Si el token no existe, mostramos estado "no encontrada" (no revelamos nada más).
        $estado = $reserva
            ? $reserva->estadoIngreso()
            : ['ok' => false, 'codigo' => 'no_encontrada', 'titulo' => 'Ticket no válido',
                'mensaje' => 'No encontramos ninguna reserva con este código.'];

        $esStaff = auth()->check() && auth()->user()->esStaff();

        return response()->view('validacion.mostrar', compact('reserva', 'estado', 'esStaff', 'token'))
            ->header('X-Robots-Tag', 'noindex, nofollow')->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store');
    }

    public function marcarUsada(string $token): RedirectResponse
    {
        try {
            return DB::transaction(function () use ($token) {
                $reserva = Reserva::where('token_validacion', $token)->lockForUpdate()->first();

                if (! $reserva) {
                    return back()->withErrors(['general' => 'Ticket no encontrado.']);
                }

                $estado = $reserva->estadoIngreso();
                if (! $estado['ok']) {
                    return back()->withErrors(['general' => 'No se puede marcar el ingreso: '.$estado['mensaje']]);
                }

                // Marca el ingreso (asignación explícita, no mass assignment).
                $reserva->usado_at = now();
                $reserva->save();

                return back()->with('success', 'Ingreso registrado correctamente.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al marcar ingreso', ['token' => $token, 'error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'No se pudo registrar el ingreso. Intenta de nuevo.']);
        }
    }
}
