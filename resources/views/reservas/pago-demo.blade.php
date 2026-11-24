<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-black text-white">Pago de prueba</h1></x-slot>
    <div class="max-w-xl mx-auto px-4 py-8">
        @include('partials.errores')
        {{-- Simulador propio: no solicita números de tarjeta, claves ni dinero. --}}
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900 mb-5">
            <h2 class="font-bold">Demostración · No se realizará ningún cobro</h2>
            <p class="text-sm mt-2">Este es un simulador local. No está conectado a Yape, a un banco ni al entorno de pruebas de Culqi.</p>
        </section>
        <section class="card p-6">
            <p class="section-label">{{ $reserva->metodo_pago === 'Tarjeta' ? 'Tarjeta de débito o crédito' : 'Yape' }} · Prueba</p>
            <h2 class="text-xl font-black mt-2">{{ $reserva->horario->cancha->nombre }}</h2>
            <p class="text-sm text-gray-600 mt-2">{{ $reserva->horario->hora_inicio->format('d/m/Y H:i') }}–{{ $reserva->horario->hora_fin->format('H:i') }}</p>
            <p class="text-3xl font-black text-green-700 my-5">S/ {{ number_format($reserva->monto_pagado, 2) }}</p>
            <p class="text-sm text-gray-600 mb-5">Cupo retenido hasta el {{ $reserva->expira_at->format('d/m/Y H:i') }}. Elige un resultado para probar el recorrido; no ingreses datos bancarios.</p>
            <form method="POST" action="{{ route('reservas.simularPago', $reserva) }}" class="space-y-3">
                @csrf
                <button name="resultado" value="aprobado" type="submit" class="btn-primary w-full">Simular pago aprobado</button>
                <button name="resultado" value="rechazado" type="submit" class="btn-outline-sm w-full justify-center">Simular pago rechazado</button>
            </form>
            <a href="{{ route('reservas.ticket', $reserva) }}" class="inline-block mt-5 text-sm font-bold text-green-700">Ver reserva pendiente</a>
        </section>
    </div>
</x-app-layout>
