<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

// Validación para crear un usuario desde el panel del administrador.
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La ruta ya está protegida por middleware role:admin; reforzamos aquí.
        return $this->user() && $this->user()->esAdmin();
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol'      => ['required', 'in:admin,recepcionista,cliente'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'activo'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo es obligatorio.',
            'email.email'        => 'Ingresa un correo válido.',
            'email.unique'       => 'Ya existe un usuario con ese correo.',
            'rol.required'       => 'Debes seleccionar un rol.',
            'rol.in'             => 'El rol seleccionado no es válido.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
