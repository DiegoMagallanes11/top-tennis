<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

class CrearAdministrador extends Command
{
    protected $signature = 'club:crear-admin {email} {--nombre=}';

    protected $description = 'Crea una cuenta administrativa nueva con contraseña privada, sin acceso público.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $nombre = $this->option('nombre') ?: $this->ask('Nombre del administrador');
        $password = $this->secret('Contraseña nueva (mínimo 12 caracteres, letras y números)');
        $confirm = $this->secret('Repite la contraseña');
        $validator = validator(['email' => $email, 'name' => $nombre, 'password' => $password, 'password_confirmation' => $confirm], [
            'email' => ['required', 'email', 'unique:users,email'], 'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        $user = new User(['name' => $nombre, 'email' => $email, 'password' => $password]);
        $user->rol = Rol::Admin;
        $user->email_verified_at = now();
        $user->save();
        $this->info('Administrador creado. La contraseña no se registra ni se muestra.');

        return self::SUCCESS;
    }
}
