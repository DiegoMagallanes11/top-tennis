{{-- Próximo partido: solo reservas del cliente autenticado. --}}
<section class="card p-5 sm:p-7 mb-6" aria-labelledby="proximo-partido">
    @if($proximaReserva?->pago_demo)<p class="text-sm font-bold text-amber-800 mb-3">Reserva de demostración · Sin cobro real ni autorización de ingreso</p>@endif
    <p class="section-label mb-2">Tu agenda</p>
    <h2 id="proximo-partido" class="text-xl font-black text-gray-900">Mi próximo partido</h2>
    @if($proximaReserva)
        @php $partido = $proximaReserva->horario; @endphp
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 mt-5">
            <div>
                <p class="font-bold text-lg">{{ $partido->cancha?->nombre ?? 'Cancha' }}</p>
                <p class="text-gray-600 mt-1">{{ $partido->hora_inicio->format('d/m/Y') }} · {{ $partido->hora_inicio->format('H:i') }}–{{ $partido->hora_fin->format('H:i') }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ (int) $partido->hora_inicio->diffInMinutes($partido->hora_fin) }} minutos · S/ {{ number_format($proximaReserva->monto_pagado, 2) }}</p>
            </div>
            <a href="{{ route('reservas.ticket', $proximaReserva) }}" class="btn-primary">Ver mi ticket</a>
        </div>
        @if($proximaReserva->pago_demo)
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-bold">{{ $proximaReserva->estado_pago === 'aprobado' ? 'Pago simulado aprobado' : 'Pago de prueba pendiente' }}</p>
                <p class="mt-1">No se realizó ningún cobro. Este ticket de demostración no autoriza ingreso.</p>
                @if($proximaReserva->estado_pago === 'pendiente' && !$proximaReserva->estaVencida() && \App\Services\ServicioPago::demoDisponible())
                    <a href="{{ route('reservas.pagoDemo', $proximaReserva) }}" class="btn-primary mt-3">Continuar pago de prueba</a>
                @endif
            </div>
        @elseif($proximaReserva->estado_pago === 'pendiente')
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-bold">Pendiente de verificación de pago</p>
                <p class="mt-1">Paga en recepción antes del {{ $proximaReserva->expira_at?->format('d/m/Y H:i') }}. Si vence ese plazo, la reserva se cancela.</p>
            </div>
        @elseif($proximaReserva->estaUsada())
            <p class="mt-5 rounded-xl bg-green-50 p-4 text-sm text-green-800">Tu ingreso ya está registrado. ¡Disfruta tu partido!</p>
        @else
            <p class="mt-5 rounded-xl bg-green-50 p-4 text-sm text-green-800">Reserva confirmada. Presenta tu QR en recepción para registrar el ingreso.</p>
        @endif
    @else
        <p class="text-gray-500 mt-3">Todavía no tienes un próximo partido. Elige una hora disponible para empezar.</p>
        <a href="{{ route('reservas.disponibles') }}" class="btn-primary mt-4">Reservar mi cancha</a>
    @endif
</section>
