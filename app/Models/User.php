<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// MODELO USER: USUARIO DEL SISTEMA CON 3 ROLES (admin, recepcionista, cliente).
// EL CAST 'rol' => Rol::class CONVIERTE EL STRING DE LA BD AL ENUM PHP AUTOMATICAMENTE.
// EL CAST 'password' => 'hashed' HASHEA LA CONTRASEÑA (BCRYPT) AL ASIGNARLA.
// USA SoftDeletes: al "eliminar" un usuario se conserva su historial de reservas.
// RELACION: UN USER TIENE MUCHAS RESERVAS (1:N).
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    // SEGURIDAD: 'rol' y 'activo' NO son asignables en masa a proposito.
    // Se asignan SIEMPRE de forma explicita en el codigo para que nadie pueda
    // enviarse rol=admin o reactivarse manipulando un formulario.
    protected $fillable = [
        'name',
        'email',
        'telefono',
        'password',
        'emoji_perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo'   => 'boolean',
        'rol'      => Rol::class,
    ];

    protected $attributes = [
        'activo' => true,
    ];

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    /** ¿Es administrador? */
    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }

    /** ¿Es personal del club (admin o recepcionista)? */
    public function esStaff(): bool
    {
        return $this->rol instanceof Rol && $this->rol->esStaff();
    }
}
