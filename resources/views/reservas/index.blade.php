<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-300 text-xs font-semibold uppercase tracking-widest mb-0.5">
                    {{ $esStaff ? 'Administración' : 'Mi cuenta' }}
                </p>
                <h1 class="text-2xl font-black text-white">
                    {{ $esStaff ? 'Reservas' : 'Mis Reservas' }}
                </h1>
            </div>
            <a href="{{ $esStaff ? route('reservas.crearManual') : route('reservas.disponibles') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                {{ $esStaff ? 'Reserva manual' : 'Nueva reserva' }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @include('partials.errores')
        <form method="GET" class="card p-4 mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div><label for="buscar" class="form-label">Buscar por código o cliente</label><input id="buscar" name="buscar" value="{{ request('buscar') }}" class="form-input" maxlength="100"></div>
            <div><label for="fecha" class="form-label">Fecha del partido</label><input id="fecha" type="date" name="fecha" value="{{ request('fecha') }}" class="form-input"></div>
            <div><label for="cancha" class="form-label">Cancha</label><select id="cancha" name="cancha" class="form-input"><option value="">Todas</option>@foreach($canchas as $cancha)<option value="{{ $cancha->id }}" @selected(request('cancha') == $cancha->id)>{{ $cancha->nombre }}</option>@endforeach</select></div>
            <div><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-input"><option value="">Todos</option>@foreach(['pendiente','confirmada','cancelada','rechazada','completada'] as $s)<option @selected(request('estado') === $s) value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div><label for="orden" class="form-label">Orden</label><select id="orden" name="orden" class="form-input"><option value="recientes">Más recientes</option><option value="antiguas" @selected(request('orden') === 'antiguas')>Más antiguas</option></select></div>
            <div class="flex items-end gap-3"><button class="btn-primary" type="submit">Filtrar</button><a class="btn-outline-sm" href="{{ route('reservas.index') }}">Limpiar</a></div>
        </form>

        @if($esStaff)
            {{-- Resumen financiero + filtro --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div class="rounded-2xl p-5 flex items-center gap-4 text-white"
                     style="background:radial-gradient(400px 160px at 90% -30%, rgba(163,230,53,.22), transparent 60%), linear-gradient(120deg,#0b3a20,#166534); box-shadow:0 12px 30px rgba(13,61,34,.25);">
                    <div class="w-12 h-12 icon-box shrink-0" style="background:rgba(255,255,255,.12);">
                        <svg class="w-6 h-6 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-green-300 text-xs font-bold uppercase tracking-wide">Ingresos confirmados</p>
                        <p class="text-3xl font-black leading-tight">S/ {{ number_format($totalIngresos, 2) }}</p>
                    </div>
                </div>

                <div class="card p-5 flex items-center gap-4">
                    <div class="w-12 h-12 icon-box shrink-0" style="background:#f0fdf4;">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                    </div>
                    <form method="GET" action="{{ route('reservas.index') }}" class="flex items-center gap-2 w-full">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wide whitespace-nowrap">Método:</label>
                        <select name="metodo_pago" onchange="this.form.submit()" class="form-input py-2 text-sm" style="width:auto; flex:1;">
                            <option value="">Todos</option>
                            <option value="Yape"     {{ request('metodo_pago') === 'Yape' ? 'selected' : '' }}>Yape</option>
                            <option value="Tarjeta" {{ request('metodo_pago') === 'Tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            <option value="Efectivo" {{ request('metodo_pago') === 'Efectivo' ? 'selected' : '' }}>Efectivo</option>
                        </select>
                    </form>
                </div>
            </div>
        @endif

        <div class="flex items-center justify-between mb-3">
            <p class="section-label">{{ $reservas->total() }} reservas encontradas</p>
        </div>

        {{-- Lista de reservas en tarjetas --}}
        <div class="space-y-3">
            @forelse($reservas as $reserva)
                @php
                    $estado = $reserva->estado_pago;
                    $mapa = [
                        'aprobado' => ['badge-green', 'Aprobado', 'Tu reserva está confirmada'],
                        'anulada'  => ['badge-red', 'Anulada', 'Esta reserva fue anulada'],
                        'cancelado_por_mantenimiento' => ['badge-yellow', 'Cancelada (mant.)', 'Cancelada por mantenimiento de la cancha'],
                        'pendiente'=> ['badge-yellow', 'Pendiente', 'Pendiente de pago en recepción'],
                    ];
                    [$badgeCls, $badgeTxt, $badgeDesc] = $mapa[$estado] ?? ['badge-gray', ucfirst($estado), ''];
                    $usado = method_exists($reserva, 'estaUsada') && $reserva->estaUsada();
                @endphp
                <div class="card card-hover p-4 sm:p-5">
                    <div class="flex flex-col gap-4">

                        {{-- Código + estado --}}
                        <div class="flex items-center gap-3 sm:w-52 shrink-0">
                            <div class="w-11 h-11 icon-box shrink-0" style="background:#f0fdf4;">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4v4H4V5zm0 6h5v5H5a1 1 0 01-1-1v-4zm11-7h4a1 1 0 011 1v4h-5V4zm5 11v4a1 1 0 01-1 1h-4v-5h5zM11 4h2v16h-2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-black tracking-wider text-green-700 leading-none">{{ $reserva->codigo_validacion }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="badge {{ $badgeCls }}">Pago: {{ $badgeTxt }}</span>
                                    <span class="badge badge-gray">{{ ucfirst($reserva->estado_reserva) }}</span>
                                    @if($usado)<span class="badge badge-gray">Usada</span>@endif
                                </div>
                                @if(!$esStaff && $badgeDesc)
                                    <p class="text-xs text-gray-400 mt-1">{{ $badgeDesc }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Detalle --}}
                        <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                            @if($esStaff)
                                <div>
                                    <p class="section-label">Cliente</p>
                                    <p class="font-semibold text-gray-800 truncate">{{ $reserva->user?->name ?? '—' }}</p>
                                </div>
                            @endif
                            <div>
                                <p class="section-label">Cancha</p>
                                <p class="font-semibold text-gray-800 truncate">{{ $reserva->horario?->cancha?->nombre ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="section-label">Fecha</p>
                                <p class="font-semibold text-gray-800">{{ optional($reserva->horario?->hora_inicio)->format('d/m/Y') ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ optional($reserva->horario?->hora_inicio)->format('H:i') ?? '' }}</p>
                            </div>
                            <div>
                                <p class="section-label">Monto</p>
                                <p class="font-black text-gray-900">S/ {{ number_format($reserva->monto_pagado, 2) }}</p>
                                <span class="badge badge-plain {{ $reserva->metodo_pago === 'Yape' ? 'badge-blue' : 'badge-gray' }} mt-0.5">{{ $reserva->metodo_pago }}{{ $reserva->pago_demo ? ' · Demostración' : '' }}</span>
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="flex flex-wrap items-center gap-2 sm:justify-end shrink-0 border-t sm:border-t-0 pt-3 sm:pt-0" style="border-color:#f1f4f1;">
                            @if($esStaff && !$reserva->pago_demo && $reserva->activa() && $reserva->estado_pago === 'pendiente' && !$reserva->estaVencida())
                                <form method="POST" action="{{ route('reservas.confirmarPago', $reserva) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-primary text-xs py-1.5 px-3" onclick="return confirm('¿Comprobaste que el club recibió este pago?')">Verificar pago</button>
                                </form>
                            @endif
                            <a href="{{ route('reservas.ticket', $reserva) }}" class="btn-outline-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                Ticket
                            </a>
                            @if($reserva->activa())
                                <a href="{{ route('reservas.edit', $reserva) }}" class="btn-outline-sm">Cambiar</a>
                                <form method="POST" action="{{ route('reservas.cancelar', $reserva) }}" onsubmit="return confirm('¿Cancelar esta reserva?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-danger">Cancelar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-12 text-center">
                    <div class="w-14 h-14 icon-box mx-auto mb-4" style="background:#f0fdf4;">
                        <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <p class="text-gray-500 font-semibold">No hay reservas registradas.</p>
                    @unless($esStaff)
                        <a href="{{ route('reservas.disponibles') }}" class="btn-primary mt-4">Reservar una cancha</a>
                    @endunless
                </div>
            @endforelse
        </div>
        <div class="mt-6">{{ $reservas->links() }}</div>
    </div>
</x-app-layout>
