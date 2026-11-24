<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-black text-white">Revisa y solicita tu reserva</h1></x-slot>
    @php
        $demo = \App\Services\ServicioPago::demoDisponible();
        $metodos = \App\Services\ServicioPago::metodosOnline();
    @endphp
    <div class="max-w-2xl mx-auto px-4 py-8">
        @include('partials.errores')
        {{-- Resumen: el importe siempre procede de la tarifa del servidor. --}}
        <section class="card p-6 mb-5">
            <h2 class="text-xl font-black mb-4">{{ $horario->cancha->nombre }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3"><dt>Fecha</dt><dd class="font-bold">{{ $horario->hora_inicio->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between gap-3"><dt>Horario</dt><dd class="font-bold">{{ $horario->hora_inicio->format('H:i') }}–{{ $horario->hora_fin->format('H:i') }}</dd></div>
                <div class="flex justify-between gap-3"><dt>Duración</dt><dd class="font-bold">{{ (int) $horario->hora_inicio->diffInMinutes($horario->hora_fin) }} minutos</dd></div>
                <div class="flex justify-between gap-3 border-t pt-4"><dt>Total {{ $demo ? 'de prueba' : 'a pagar' }}</dt><dd class="text-2xl font-black text-green-700">S/ {{ number_format($horario->tarifa->precio, 2) }}</dd></div>
            </dl>
        </section>
        {{-- Identificar el simulador sin mostrar un QR que pueda recibir dinero. --}}
        @if($demo)
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-5 text-sm text-amber-900">
                <h2 class="font-bold">Modo de demostración · Sin cobros reales</h2>
                <p class="mt-2">Puedes probar Yape o tarjeta. No envíes dinero ni ingreses datos bancarios. El ticket de prueba no autoriza ingreso al club.</p>
            </section>
        @endif
        <section class="card p-4 mb-5 text-sm text-gray-700">
            <p>Completa {{ $demo ? 'la prueba de pago' : 'la verificación del pago' }} antes del {{ $horario->hora_inicio->copy()->subMinutes(\App\Models\Reserva::MINUTOS_GRACIA)->format('d/m/Y H:i') }}. Si vence, se libera la cancha.</p>
            @php $limiteCambios = $horario->hora_inicio->copy()->subHours(\App\Models\ClubSetting::reglas()['cancelacion_horas']); @endphp
            <p class="mt-2">Cambios y cancelación desde tu cuenta: hasta el {{ $limiteCambios->format('d/m/Y H:i') }}. {{ $limiteCambios->isPast() ? 'Ese plazo ya terminó; coordina cualquier cambio con recepción.' : 'Las diferencias de precio y devoluciones se coordinan con recepción.' }}</p>
        </section>
        @if(count($metodos))
            <form method="POST" action="{{ route('reservas.store') }}" class="card p-6 space-y-5">
                @csrf
                <input type="hidden" name="horario_id" value="{{ $horario->id }}">
                <input type="hidden" name="clave_solicitud" value="{{ old('clave_solicitud', (string) \Illuminate\Support\Str::uuid()) }}">
                <fieldset>
                    <legend class="form-label mb-3">Elige cómo {{ $demo ? 'probar el pago' : 'pagar' }}</legend>
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach($metodos as $metodo)
                            <label class="flex items-center gap-3 border border-gray-200 rounded-xl p-4 cursor-pointer">
                                <input type="radio" name="metodo_pago" value="{{ $metodo }}" required @checked(old('metodo_pago', $metodos[0]) === $metodo) class="text-green-600 focus:ring-green-500">
                                <span><span class="block font-bold">{{ $metodo === 'Tarjeta' ? 'Tarjeta de débito o crédito' : 'Yape' }}</span><span class="block text-xs text-gray-500 mt-1">{{ $demo ? 'Pago simulado' : 'Verificación en recepción' }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div><label for="telefono" class="form-label">Teléfono de contacto (opcional)</label><input id="telefono" name="telefono" type="tel" maxlength="20" autocomplete="tel" class="form-input" value="{{ old('telefono', auth()->user()->telefono) }}"></div>
                @unless($demo)
                    <div><label for="numero_operacion" class="form-label">Número de operación Yape</label><input id="numero_operacion" name="numero_operacion" required maxlength="50" class="form-input" value="{{ old('numero_operacion') }}"><p class="text-sm text-gray-500 mt-2">Recepción debe comprobar el cobro. Un número de operación no confirma el pago.</p></div>
                @endunless
                <button type="submit" class="btn-primary w-full">{{ $demo ? 'Continuar al pago de prueba' : 'Solicitar reserva con Yape' }}</button>
            </form>
        @else
            <p class="card p-5 text-gray-700">Los pagos online aún no están habilitados. Consulta con recepción.</p>
        @endif
        <a href="{{ route('reservas.disponibles') }}" class="btn-outline-sm mt-5">Volver a los horarios</a>
    </div>
</x-app-layout>
