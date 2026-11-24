<?php

namespace App\Enums;

// ENUM DE ROLES: USAMOS ENUMS DE PHP 8.1 (BACKED ENUM) PARA TIPAR LOS ROLES.
// EL VALOR STRING SE GUARDA EN LA COLUMNA 'rol' DE LA TABLA USERS.
//   Admin         -> acceso total (usuarios, canchas, tarifas, horarios, pagos, reportes).
//   Recepcionista -> personal de mostrador: calendario, reservas manuales y cobro en caja.
//   Cliente       -> solo reserva y ve sus propias reservas.
enum Rol: string
{
    case Admin        = 'admin';
    case Recepcionista = 'recepcionista';
    case Cliente      = 'cliente';

    /** Etiqueta legible para mostrar en la interfaz. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Admin        => 'Administrador',
            self::Recepcionista => 'Recepcionista',
            self::Cliente      => 'Cliente',
        };
    }

    /** ¿Es personal del club (admin o recepcionista)? */
    public function esStaff(): bool
    {
        return $this === self::Admin || $this === self::Recepcionista;
    }
}
