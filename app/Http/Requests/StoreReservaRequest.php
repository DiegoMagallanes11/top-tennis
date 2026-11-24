<?php

namespace App\Http\Requests;

use App\Models\Reserva;
use App\Services\ServicioPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'clave_solicitud' => ['nullable', 'uuid'],
            'telefono' => ['nullable', 'string', 'regex:/^[+0-9 ()-]{6,20}$/'],
            'horario_id' => ['required', 'integer', 'exists:horarios,id'],
            'metodo_pago' => ['required', Rule::in(ServicioPago::metodosOnline())],
            'numero_operacion' => ['nullable', Rule::requiredIf(fn () => $this->metodo_pago === 'Yape' && ! ServicioPago::demoDisponible()),
                'string', 'max:50', Rule::unique('reservas', 'numero_operacion')->ignore($this->reservaReintentada()?->id)],
        ];
    }

    private function reservaReintentada(): ?Reserva
    {
        if (! is_string($this->clave_solicitud)) {
            return null;
        }

        return Reserva::withTrashed()->where('user_id', $this->user()->id)->where('clave_solicitud', $this->clave_solicitud)->first();
    }

    public function messages(): array
    {
        return [
            'horario_id.required' => 'Elige un horario para reservar.',
            'horario_id.exists' => 'El horario indicado no existe.',
            'metodo_pago.required' => 'Elige un método de pago.',
            'metodo_pago.in' => 'Elige un método online habilitado. Efectivo solo está disponible en recepción.',
            'numero_operacion.required' => 'Ingresa el número de operación de Yape.',
            'numero_operacion.unique' => 'Ese número de operación ya fue utilizado.',
        ];
    }
}
