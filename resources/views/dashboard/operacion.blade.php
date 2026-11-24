{{-- Operación diaria: disponible para administración y recepción. --}}
@if($publicacion['estado'] === 'faltan_tarifas')
    <section class="rounded-xl border border-amber-200 bg-amber-50 p-5 mb-6 text-amber-900">
        <h2 class="font-bold">Configura las tarifas para los horarios automáticos</h2>
        <p class="text-sm mt-2">Falta una tarifa de día o de noche. Los horarios existentes se conservan.</p>
        @if(auth()->user()->esAdmin())
            <a href="{{ route('club.edit') }}" class="btn-primary mt-4">Configurar horarios automáticos</a>
        @else
            <p class="text-sm mt-3">Solicita al administrador que revise las tarifas en Reglas del club.</p>
        @endif
    </section>
@endif
{{-- Avisar antes de que se agoten las fechas publicadas; no generar cupos sin revisión. --}}
@if($publicacion['estado'] === 'desactivada' && (!$ultimoHorarioPublicado || \Illuminate\Support\Carbon::parse($ultimoHorarioPublicado)->startOfDay()->lt(today()->addDays(6))))
    <section class="rounded-xl border border-amber-200 bg-amber-50 p-5 mb-6 text-amber-900" aria-labelledby="aviso-horarios">
        <h2 id="aviso-horarios" class="font-bold">{{ $ultimoHorarioPublicado ? 'Publica los próximos horarios' : 'No hay horarios futuros publicados' }}</h2>
        <p class="text-sm mt-2">
            @if($ultimoHorarioPublicado)
                Los horarios de canchas operativas llegan hasta el {{ \Illuminate\Support\Carbon::parse($ultimoHorarioPublicado)->format('d/m/Y') }}. Publica más fechas para que los clientes sigan reservando.
            @else
                Los clientes no pueden reservar sin horarios futuros. Publica nuevas fechas con las tarifas y reglas del club.
            @endif
        </p>
        @if(auth()->user()->esAdmin())
            <a href="{{ route('horarios.create') }}" class="btn-primary mt-4">Publicar horarios</a>
        @else
            <p class="text-sm font-semibold mt-3">Solicita al administrador que publique los próximos horarios.</p>
        @endif
    </section>
@endif

<section class="card p-5 sm:p-6 mb-6" aria-labelledby="agenda-hoy">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div><p class="section-label">{{ today()->format('d/m/Y') }} · Lima</p><h2 id="agenda-hoy" class="text-xl font-black mt-1">Agenda de hoy</h2></div>
        <a href="{{ route('reservas.index', ['fecha' => today()->toDateString()]) }}" class="btn-outline-sm">Ver todas ({{ $totalAgenda }})</a>
    </div>
    <div class="grid md:grid-cols-2 gap-3">
        @forelse($agendaHoy as $reserva)
            <article class="rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap justify-between gap-2">
                    <p class="font-black">{{ $reserva->horario->hora_inicio->format('H:i') }}–{{ $reserva->horario->hora_fin->format('H:i') }}</p>
                    @if($reserva->pago_demo)<span class="text-xs font-bold text-amber-800">Demostración · Sin cobro real</span>@endif
                    <span class="text-xs font-bold text-gray-600">{{ ucfirst($reserva->estado_reserva) }} · Pago {{ strtolower($reserva->estado_pago) }}</span>
                </div>
                <p class="font-bold mt-2">{{ $reserva->horario->cancha?->nombre }} · {{ $reserva->contacto['nombre'] ?? $reserva->user?->name ?? 'Cliente' }}</p>
                <p class="text-sm text-gray-500 mt-1">S/ {{ number_format($reserva->monto_pagado, 2) }} · {{ $reserva->estaUsada() ? 'Ingreso registrado' : 'Sin ingreso registrado' }}</p>
                @if($telefono = $reserva->contacto['telefono'] ?? $reserva->user?->telefono)
                    <p class="text-sm text-gray-600 mt-1">Contacto: {{ $telefono }}</p>
                @endif
                <a href="{{ route('reservas.ticket', $reserva) }}" class="inline-flex py-2 mt-2 text-sm font-bold text-green-700">Ver ticket y gestionar →</a>
            </article>
        @empty
            <p class="text-gray-500 text-sm py-4">No hay reservas para hoy. Puedes consultar el calendario o registrar una reserva en recepción.</p>
        @endforelse
    </div>
    @if($totalAgenda > 30)<p class="mt-4 text-sm text-gray-500">Se muestran los primeros 30 partidos. Consulta todas las reservas del día para ver el resto.</p>@endif
</section>

<section class="card p-5 sm:p-6 mb-6" aria-labelledby="pagos-pendientes">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
        <div><p class="section-label">Primero los que vencen antes</p><h2 id="pagos-pendientes" class="text-xl font-black mt-1">Pagos pendientes ({{ $totalPendientes }})</h2></div>
        <a href="{{ route('reservas.index', ['estado' => 'pendiente']) }}" class="btn-outline-sm">Ver pendientes</a>
    </div>
    <div class="grid md:grid-cols-2 gap-3">
        @forelse($pagosPendientes as $reserva)
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="font-bold">{{ $reserva->contacto['nombre'] ?? $reserva->user?->name ?? 'Cliente' }} · S/ {{ number_format($reserva->monto_pagado, 2) }}</p>
                <p class="text-sm text-amber-900 mt-1">Vence: {{ $reserva->expira_at->format('d/m/Y H:i') }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ $reserva->horario->cancha?->nombre }} · Partido {{ $reserva->horario->hora_inicio->format('d/m/Y H:i') }}</p>
                <a href="{{ route('reservas.index', ['buscar' => $reserva->codigo_validacion]) }}" class="inline-flex py-2 mt-2 text-sm font-bold text-green-800">Revisar y verificar pago →</a>
            </article>
        @empty
            <p class="text-sm text-gray-500 py-3">No hay pagos pendientes de verificación.</p>
        @endforelse
    </div>
    @if($totalPendientes > 8)<p class="mt-4 text-sm text-gray-500">Se muestran los 8 vencimientos más próximos. Usa «Ver pendientes» para revisar todos.</p>@endif
</section>
