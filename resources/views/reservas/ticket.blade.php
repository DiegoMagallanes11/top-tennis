<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Ticket de Reserva</h1>
            <p class="text-green-200 text-sm mt-0.5">Comprobante digital · {{ $reserva->codigo_validacion }}</p>
        </div>
    </x-slot>

    <div class="max-w-md mx-auto px-4 py-8">

        <div class="card overflow-hidden">
            {{-- Cabecera con logo oficial --}}
            <div class="text-center py-7" style="background: linear-gradient(135deg,#0d3d22,#14532d);">
                <div class="mx-auto w-20 h-20 rounded-full bg-green-400 flex items-center justify-center mb-3">
                    <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
                        <circle cx="28" cy="28" r="26" fill="#dcfce7" stroke="#15803d" stroke-width="3"/>
                        <path d="M6 22 Q28 13 50 22" stroke="#15803d" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                        <path d="M6 34 Q28 43 50 34" stroke="#15803d" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="text-white font-extrabold text-2xl tracking-wide">TOP TENNIS</h2>
                <p class="text-green-200 text-xs mt-0.5">Ticket de Reserva</p>
            </div>

            <div class="text-center py-3 border-b border-dashed border-gray-200">
                @if($reserva->pago_demo)
                    <span class="badge badge-yellow">DEMOSTRACIÓN · {{ $reserva->estado_pago === 'aprobado' ? 'PAGO SIMULADO' : 'PRUEBA PENDIENTE' }}</span>
                @elseif($reserva->estado_pago === 'aprobado')
                    <span class="badge badge-green">✓ PAGADO</span>
                @elseif($reserva->estado_pago === 'anulada')
                    <span class="badge badge-red">✕ ANULADA</span>
                @elseif($reserva->estado_pago === 'cancelado_por_mantenimiento')
                    <span class="badge badge-yellow">✕ CANCELADA POR MANTENIMIENTO</span>
                @else
                    <span class="badge badge-yellow">⏳ PAGO PENDIENTE</span>
                @endif
            </div>

            @if($reserva->estado_pago === 'pendiente' && $reserva->metodo_pago === 'Efectivo')
                <div class="mx-5 mt-4 p-3 rounded-xl bg-red-50 border border-red-300">
                    <p class="text-xs font-bold text-red-700 leading-snug">
                        ⚠ PAGO PENDIENTE: Tiene hasta 30 minutos antes de su hora de inicio
                        ({{ optional($reserva->expira_at)->format('d/m/Y H:i') }}) para pagar presencialmente,
                        de lo contrario su reserva será anulada.
                    </p>
                </div>
            @elseif($reserva->estado_pago === 'anulada')
                <div class="mx-5 mt-4 p-3 rounded-xl bg-red-50 border border-red-300">
                    <p class="text-xs font-bold text-red-700">
                        Esta reserva fue anulada por falta de pago. El horario fue liberado.
                    </p>
                </div>
            @elseif($reserva->estado_pago === 'cancelado_por_mantenimiento')
                <div class="mx-5 mt-4 p-3 rounded-xl bg-amber-50 border border-amber-300">
                    <p class="text-xs font-bold text-amber-700">
                        Esta reserva fue cancelada porque la cancha entró en mantenimiento.
                        Comunícate con recepción para gestionar tu reembolso.
                    </p>
                </div>
            @endif

            <div class="text-center py-5">
                <p class="text-xs text-gray-400 uppercase tracking-widest">Código de validación</p>
                <p class="text-3xl font-extrabold text-green-700 tracking-wider">{{ $reserva->codigo_validacion }}</p>
            </div>

            @if($reserva->pago_demo)
                <div class="m-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-bold">Ticket de demostración · No válido para ingreso</p>
                    <p class="mt-2">No se realizó ningún cobro real.</p>
                    @if($reserva->activa() && !$reserva->estaVencida() && $reserva->estado_pago === 'pendiente' && $reserva->user_id === auth()->id() && \App\Services\ServicioPago::demoDisponible())
                        <a class="btn-primary mt-3" href="{{ route('reservas.pagoDemo', $reserva) }}">Continuar pago de prueba</a>
                    @endif
                </div>
            @endif
            <div class="flex justify-center pb-5">
                @if($reserva->pago_demo)
                    <p class="text-sm text-gray-500">Demostración sin QR de ingreso.</p>
                @elseif($qrSvg)
                    <div class="p-2 border border-gray-200 rounded-xl bg-white" style="width:240px;">
                        {!! $qrSvg !!}
                    </div>
                @else
                    <div class="p-6 border border-dashed border-gray-200 rounded-xl bg-gray-50 text-center" style="width:240px;">
                        <p class="text-xs text-gray-400">Código QR no disponible.</p>
                        <p class="text-xs text-gray-400 mt-0.5">Muestre el código de validación en recepción.</p>
                    </div>
                @endif
            </div>

            <div class="px-7 pb-6 text-sm">
                @foreach([
                    ['Cliente', $reserva->user?->name ?? '—'],
                    ['Cancha', $reserva->horario?->cancha?->nombre ?? '—'],
                    ['Fecha', optional($reserva->horario?->hora_inicio)->format('d/m/Y') ?? '—'],
                    ['Horario', (optional($reserva->horario?->hora_inicio)->format('H:i') ?? '—').' – '.(optional($reserva->horario?->hora_fin)->format('H:i') ?? '—')],
                    ['Método de pago', $reserva->metodo_pago],
                ] as [$k, $v])
                    <div class="flex justify-between py-2 border-t border-gray-100">
                        <span class="text-gray-500">{{ $k }}</span>
                        <span class="font-semibold text-gray-800">{{ $v }}</span>
                    </div>
                @endforeach
                @if($reserva->numero_operacion)
                    <div class="flex justify-between py-2 border-t border-gray-100">
                        <span class="text-gray-500">N° operación</span>
                        <span class="font-semibold text-gray-800">{{ $reserva->numero_operacion }}</span>
                    </div>
                @endif
                <div class="flex justify-between py-2 border-t border-gray-100">
                    <span class="text-gray-500">Monto</span>
                    <span class="font-extrabold text-green-700 text-base">S/ {{ number_format($reserva->monto_pagado, 2) }}</span>
                </div>
            </div>

            <div class="bg-green-50 text-center px-6 py-4 border-t border-dashed border-gray-200">
                <p class="text-xs text-green-800 font-medium">
                    {{ $reserva->pago_demo ? 'Este ticket sirve únicamente para probar el recorrido de reserva.' : 'Muestre este ticket digital en la recepción del establecimiento.' }}
                </p>
            </div>
        </div>

        <div class="flex justify-center gap-3 mt-6">
            <a href="{{ route('reservas.ticket.pdf', $reserva) }}" class="btn-primary py-2 px-6">⬇ Descargar PDF</a>
            <a href="{{ route('reservas.index') }}" class="btn-outline-sm py-2 px-5">Mis reservas</a>
        </div>
    </div>
    <div class="max-w-2xl mx-auto p-4">
        <div class="card p-5 space-y-3">
            <p class="font-bold">Estado de la reserva: {{ ucfirst($reserva->estado_reserva) }}</p>
            <p class="text-sm">Duración: {{ $reserva->horario?->hora_inicio?->diffInMinutes($reserva->horario?->hora_fin) }} minutos · Zona horaria: {{ config('app.timezone') }}</p>
            <p class="text-sm">Contacto registrado: {{ $reserva->contacto['nombre'] ?? $reserva->user?->name }} · {{ $reserva->contacto['telefono'] ?? $reserva->user?->telefono ?? 'Sin teléfono' }}</p>
            @if($reserva->motivo)<p>{{ e($reserva->motivo) }}</p>@endif
            @if(!$reserva->pago_demo && $reserva->estado_pago === 'pendiente')<p class="text-amber-800">El pago no está verificado. Este ticket no autoriza el ingreso hasta que recepción confirme el cobro.</p>@endif
            @if($reserva->activa())<a class="btn-outline-sm" href="{{ route('reservas.edit', $reserva) }}">Cambiar horario</a>@endif
            @if(auth()->user()->esStaff() && $reserva->activa())
                <form method="POST" action="{{ route('reservas.gestionar', $reserva) }}" onsubmit="return confirm('¿Guardar este cambio de estado?')">@csrf @method('PATCH')
                    <label class="form-label" for="accion">Gestión del club</label><select id="accion" name="accion" class="form-input"><option value="rechazar">Rechazar reserva</option><option value="completar">Marcar completada (partido finalizado)</option></select>
                    <label for="motivo" class="form-label mt-3">Motivo</label><input id="motivo" name="motivo" maxlength="500" class="form-input"><button type="submit" class="btn-primary mt-3">Actualizar estado</button>
                </form>
            @endif
            <h2 class="font-bold pt-3">Historial de cambios</h2>
            @forelse($reserva->eventos as $evento)<p class="text-sm text-gray-600">{{ $evento->created_at->format('d/m/Y H:i') }} · {{ str_replace('_',' ', $evento->accion) }}</p>@empty<p class="text-sm text-gray-500">Reserva anterior al registro de cambios.</p>@endforelse
        </div>
    </div>
</x-app-layout>
