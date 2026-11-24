<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// CORREO DE CONFIRMACIÓN DE RESERVA
// Se envía automáticamente al cliente al completar una reserva.
// En desarrollo (MAIL_MAILER=log) el correo se escribe en storage/logs/laravel.log.
// En producción, configurar un servicio SMTP real (Brevo, Resend, Gmail...).
class ReservaConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reserva $reserva)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de tu reserva ' . $this->reserva->codigo_validacion,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reserva-confirmada',
        );
    }
}
