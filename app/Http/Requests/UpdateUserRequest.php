<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// Validación para editar un usuario desde el panel del administrador.
// La contraseña es opcional: solo se cambia si se escribe una nueva.
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('usuario')->id ?? null;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol'      => ['required', 'in:admin,recepcionista,cliente'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
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
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
