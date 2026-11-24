<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::warning('Falló el envío de recuperación', ['tipo' => get_class($e)]);
        }
        $mensaje = in_array(config('mail.default'), ['log', 'array'])
            ? 'Solicitud registrada en modo local. No se envía correo externo hasta configurar el servicio de correo.'
            : 'Solicitud registrada. Si existe una cuenta y el correo está disponible, recibirás instrucciones. Si no llegan, intenta más tarde o comunícate con el club.';

        return back()->with('status', $mensaje);
    }
}
