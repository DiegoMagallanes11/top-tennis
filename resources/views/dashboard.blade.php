<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-300 text-xs font-semibold uppercase tracking-widest mb-0.5">
                    {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                </p>
                <h1 class="text-2xl font-black text-white leading-tight">
                    Hola, {{ explode(' ', Auth::user()->name)[0] }} 👋
                </h1>
            </div>
            {{-- Badge de rol --}}
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold"
                  style="background:rgba(255,255,255,.12); color:#a7f3d0; border:1px solid rgba(255,255,255,.15);">
                @if($esStaff)
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M4.5 9Q12 5.5 19.5 9M4.5 15Q12 18.5 19.5 15" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
                @endif
                {{ ucfirst(Auth::user()->rol->value ?? 'usuario') }}
            </span>
        </div>
    </x-slot>

    @if($esStaff)
    {{-- ══════════ PANEL ADMIN / RECEPCIÓN ══════════ --}}
    {{-- x-data envuelve el modal de mantenimiento Y el widget que lo dispara (acceso rápido, fuera del CRUD de canchas) --}}
    <div x-data="{
            open: false,
            canchaId: null,
            canchaNombre: '',
            motivo: '',
            finMantenimiento: '',
            abrir(id, nombre) {
                this.canchaId = id;
                this.canchaNombre = nombre;
                this.motivo = '';
                this.finMantenimiento = '';
                this.open = true;
            }
         }"
         x-cloak>

        {{-- Modal de Mantenimiento --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="open = false"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 flex items-center justify-center p-4">

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">

                {{-- Header modal --}}
                <div class="px-6 pt-6 pb-4 border-b border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                 style="background:#fef3c7;">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Poner en mantenimiento</h3>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="canchaNombre"></p>
                            </div>
                        </div>
                        <button @click="open = false"
                                class="text-gray-400 hover:text-gray-600 transition-colors ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Formulario --}}
                <form method="POST" :action="`/canchas/${canchaId}/mantenimiento`">
                    @csrf
                    <div class="px-6 py-5 space-y-4">

                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">
                            <strong>Atención:</strong> Las reservas activas que coincidan con este
                            período serán canceladas. Los pagos se conservan en el historial; cualquier devolución debe gestionarse en recepción.
                        </div>

                        <div>
                            <label class="form-label">Motivo del mantenimiento</label>
                            <input type="text" name="motivo_mantenimiento"
                                   x-model="motivo"
                                   placeholder="Ej. Reparación de red, Pintura de líneas…"
                                   class="form-input mt-1"
                                   maxlength="255" required>
                        </div>

                        <div>
                            <label class="form-label">Fin estimado del mantenimiento</label>
                            <input type="datetime-local" name="fin_mantenimiento"
                                   x-model="finMantenimiento"
                                   class="form-input mt-1"
                                   :min="new Date().toISOString().slice(0, 16)"
                                   required>
                            <p class="text-xs text-gray-400 mt-1">La cancha se restaurará automáticamente al llegar esta fecha.</p>
                        </div>

                    </div>

                    <div class="px-6 pb-6 flex justify-end gap-3">
                        <button type="button" @click="open = false"
                                class="btn-outline-sm py-2 px-5">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="py-2 px-6 rounded-xl text-sm font-bold text-white transition-all duration-200"
                                style="background:#d97706; box-shadow:0 2px 8px rgba(217,119,6,.3);"
                                onmouseover="this.style.opacity='.88'"
                                onmouseout="this.style.opacity='1'">
                            Confirmar mantenimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
                <div class="card p-4"><p class="section-label">Cancelaciones y rechazos</p><p class="text-2xl font-black">{{ $stats['cancelaciones'] }}</p></div>
                <div class="card p-4"><p class="section-label">Partidos completados</p><p class="text-2xl font-black">{{ $stats['completadas'] }}</p></div>
                <div class="card p-4"><p class="section-label">Ocupación de horarios publicados esta semana</p><p class="text-2xl font-black">{{ $stats['ocupacion'] }}%</p></div>
            </div>

            @include('partials.errores')

            @include('dashboard.operacion')

            @if(auth()->user()->esAdmin())
            {{-- Estado de canchas: acceso rápido a mantenimiento, separado del CRUD de canchas --}}
            <div class="card overflow-hidden mb-6">
                <div class="px-5 py-3.5 border-b border-gray-50 flex items-center justify-between">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Estado de canchas</p>
                    <a href="{{ route('canchas.index') }}" class="text-xs font-semibold text-green-600 hover:underline">
                        Gestionar canchas →
                    </a>
                </div>
                <div class="p-4 flex gap-3 overflow-x-auto">
                    @forelse($canchas as $cancha)
                        <div class="shrink-0 w-56 rounded-xl border p-3.5
                                    {{ $cancha->estado_mantenimiento === 'operativa' ? 'border-gray-100' : 'border-amber-200 bg-amber-50/40' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-gray-900 text-sm truncate">{{ $cancha->nombre }}</span>
                                @if($cancha->estado_mantenimiento === 'operativa')
                                    <span class="badge badge-green text-xs shrink-0">Operativa</span>
                                @else
                                    <span class="badge badge-yellow text-xs shrink-0">Mantenimiento</span>
                                @endif
                            </div>

                            @if($cancha->estado_mantenimiento === 'en_mantenimiento' && $cancha->fin_mantenimiento)
                                <p class="text-xs text-amber-600 mb-2">
                                    Hasta {{ $cancha->fin_mantenimiento->format('d/m/Y H:i') }}
                                </p>
                            @endif

                            @if($cancha->estado_mantenimiento === 'operativa')
                                <button type="button"
                                        @click="abrir({{ $cancha->id }}, '{{ addslashes($cancha->nombre) }}')"
                                        class="w-full text-xs py-1.5 px-3 rounded-lg border font-semibold transition-all duration-200
                                               border-amber-400 text-amber-600 hover:bg-amber-50">
                                    Poner en mantenimiento
                                </button>
                            @else
                                <form method="POST" action="{{ route('canchas.restaurar', $cancha) }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-xs py-1.5 px-3 rounded-lg border font-semibold transition-all duration-200
                                                   border-green-500 text-green-600 hover:bg-green-50"
                                            onclick="return confirm('¿Restaurar la cancha a operativa?')">
                                        Restaurar
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-2">No hay canchas registradas.</p>
                    @endforelse
                </div>
            </div>

            @endif

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                @php
                $statItems = [
                    [
                        'label' => 'Canchas',
                        'value' => $stats['canchas'],
                        'sub'   => $stats['canchas_operativas'].' operativas',
                        'color' => '#15803d',
                        'bg'    => '#f0fdf4',
                        'icon'  => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M3.5 9Q12 5.5 20.5 9M3.5 15Q12 18.5 20.5 15" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Tarifas',
                        'value' => $stats['tarifas'],
                        'sub'   => 'configuradas',
                        'color' => '#1d4ed8',
                        'bg'    => '#eff6ff',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'label' => 'Horarios libres',
                        'value' => $stats['horarios_disp'],
                        'sub'   => 'en las fechas publicadas',
                        'color' => '#7c3aed',
                        'bg'    => '#f5f3ff',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                    ],
                    [
                        'label' => 'Reservas',
                        'value' => $stats['reservas'],
                        'sub'   => 'registradas',
                        'color' => '#b45309',
                        'bg'    => '#fffbeb',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
                    ],
                ];
                @endphp

                @foreach($statItems as $s)
                    <div class="stat-card">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                 style="background:{{ $s['bg'] }};">
                                <svg class="w-5 h-5" fill="none" stroke="{{ $s['color'] }}" viewBox="0 0 24 24">
                                    {!! $s['icon'] !!}
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-black text-gray-900 leading-none">{{ $s['value'] }}</p>
                        <p class="text-sm font-semibold text-gray-700 mt-1.5">{{ $s['label'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $s['sub'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Ingresos banner --}}
            <div class="rounded-2xl p-6 mb-8 flex items-center justify-between"
                 style="background: linear-gradient(135deg, #0d3d22, #155e36); border: 1px solid rgba(255,255,255,.06);">
                <div>
                    <p class="text-green-300 text-xs font-bold uppercase tracking-widest mb-1">Ingresos confirmados</p>
                    <p class="text-4xl font-black text-white">S/ {{ number_format($stats['ingresos'], 2) }}</p>
                    <p class="text-green-400 text-xs mt-1">Basado en montos pagados registrados</p>
                </div>
                <div class="hidden sm:flex flex-col items-end gap-2">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                         style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1);">
                        <svg class="w-7 h-7 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <a href="{{ route('reservas.index') }}"
                       class="text-xs font-semibold text-green-300 hover:text-white transition-colors">
                        Ver desglose →
                    </a>
                </div>
            </div>

            {{-- ══════════ MÉTRICAS DEL NEGOCIO ══════════ --}}
            @isset($metrics)
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Métricas del negocio</p>
            <p class="text-sm text-gray-500 mb-4">Los importes aprobados se agrupan por la fecha de creación de la reserva. No representan un cierre de caja ni descuentan devoluciones.</p>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
                @php
                $kpis = [
                    ['Reservas creadas hoy', 'S/ '.number_format($metrics['ingresosHoy'], 2), '#15803d'],
                    ['Esta semana',      'S/ '.number_format($metrics['ingresosSemana'], 2), '#15803d'],
                    ['Este mes',         'S/ '.number_format($metrics['ingresosMes'], 2),    '#15803d'],
                    ['Reservas activas', $metrics['reservasActivas'],                        '#1d4ed8'],
                    ['Canceladas o rechazadas', $metrics['tasaCancelacion'].'%',             '#b45309'],
                ];
                @endphp
                @foreach($kpis as [$lbl, $val, $col])
                    <div class="card p-4">
                        <p class="text-xs font-semibold text-gray-400">{{ $lbl }}</p>
                        <p class="text-xl font-black mt-1" style="color:{{ $col }};">{{ $val }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
                <div class="card p-5 lg:col-span-2">
                    <p class="text-sm font-bold text-gray-700 mb-3">Importes aprobados por fecha de creación de la reserva</p>
                    <div style="height:170px;"><canvas id="chartIngresos"></canvas></div>
                </div>
                <div class="card p-5">
                    <p class="text-sm font-bold text-gray-700 mb-3">Canchas más usadas</p>
                    <div class="space-y-2 mb-5">
                        @forelse($metrics['topCanchas'] as $c)
                            <div class="flex justify-between text-sm"><span class="text-gray-600 truncate">{{ $c->nombre }}</span><span class="font-bold text-gray-900">{{ $c->total }}</span></div>
                        @empty
                            <p class="text-xs text-gray-400">Sin datos aún.</p>
                        @endforelse
                    </div>
                    <p class="text-sm font-bold text-gray-700 mb-3">Horas más demandadas</p>
                    <div class="space-y-2">
                        @forelse($metrics['topHorarios'] as $hh)
                            <div class="flex justify-between text-sm"><span class="text-gray-600">{{ sprintf('%02d:00', $hh->hora) }}</span><span class="font-bold text-gray-900">{{ $hh->total }}</span></div>
                        @empty
                            <p class="text-xs text-gray-400">Sin datos aún.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('tt:chart-ready', function () {
                    var serie = @json($metrics['serieDias']);
                    var el = document.getElementById('chartIngresos');
                    if (!el || typeof Chart === 'undefined') return;
                    new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: serie.map(function (d) { return d.dia; }),
                            datasets: [{ label: 'Importe aprobado (S/)', data: serie.map(function (d) { return d.total; }), backgroundColor: '#16a34a', borderRadius: 6 }]
                        },
                        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                    });
                });
            </script>
            @endisset

            {{-- Accesos rápidos --}}
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Gestión rápida</p>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @php
                $accesos = auth()->user()->esAdmin() ? [
                    ['Canchas',  'canchas.index',  '#f0fdf4', '#15803d', '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M3.5 9Q12 5.5 20.5 9M3.5 15Q12 18.5 20.5 15" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/>', 'Gestiona canchas'],
                    ['Tarifas',  'tarifas.index',  '#eff6ff', '#1d4ed8', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'Precios y planes'],
                    ['Horarios', 'horarios.index', '#f5f3ff', '#7c3aed', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>', 'Slots de canchas'],
                    ['Reservas', 'reservas.index', '#fffbeb', '#b45309', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>', 'Historial y pagos'],
                ] : [
                    ['Calendario',     'calendario.index',     '#f0fdf4', '#15803d', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>', 'Disponibilidad en vivo'],
                    ['Reserva manual', 'reservas.crearManual', '#eff6ff', '#1d4ed8', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>', 'Registrar en mostrador'],
                    ['Reservas',       'reservas.index',       '#fffbeb', '#b45309', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>', 'Historial y pagos'],
                ];
                @endphp

                @foreach($accesos as [$lbl, $ruta, $bg, $color, $icon, $desc])
                    <a href="{{ route($ruta) }}"
                       class="card p-5 hover:shadow-md transition-all duration-200 group"
                       style="hover:border-color:#e0e0e0;">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 transition-transform duration-200 group-hover:scale-110"
                             style="background:{{ $bg }};">
                            <svg class="w-5 h-5" fill="none" stroke="{{ $color }}" viewBox="0 0 24 24">
                                {!! $icon !!}
                            </svg>
                        </div>
                        <p class="font-bold text-gray-900 text-sm">{{ $lbl }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $desc }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @else
    {{-- ══════════ PANEL CLIENTE ══════════ --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Banner de reserva prominente --}}
        <div class="rounded-2xl p-6 sm:p-8 mb-8 relative overflow-hidden"
             style="background: linear-gradient(135deg, #0d3d22, #155e36); border: 1px solid rgba(255,255,255,.06);">
            <div class="relative z-10">
                <p class="text-green-300 text-xs font-bold uppercase tracking-widest mb-2">Reserva tu cancha</p>
                <h2 class="text-2xl sm:text-3xl font-black text-white mb-3">¿Listo para jugar?</h2>
                <p class="text-green-200 text-sm sm:text-base mb-5 max-w-xl">Elige tu horario y guarda tu solicitud. Recepción confirma la reserva después de verificar el pago.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('reservas.disponibles') }}"
                       class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-400 text-white font-bold px-6 py-3 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M3.5 9Q12 5.5 20.5 9M3.5 15Q12 18.5 20.5 15" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        </svg>
                        Reservar ahora
                    </a>
                    <a href="{{ route('canchas.publico') }}"
                       class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-bold px-6 py-3 rounded-xl transition-all duration-200 border border-white/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Ver canchas
                    </a>
                </div>
            </div>
            <div class="absolute right-0 top-0 w-64 h-full opacity-10">
                <svg viewBox="0 0 200 200" fill="none" class="w-full h-full">
                    <circle cx="100" cy="100" r="80" stroke="white" stroke-width="2"/>
                    <path d="M20 70 Q100 30 180 70" stroke="white" stroke-width="2" fill="none"/>
                    <path d="M20 130 Q100 170 180 130" stroke="white" stroke-width="2" fill="none"/>
                </svg>
            </div>
        </div>

        @include('dashboard.proximo-partido')

        {{-- Stats cliente --}}
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="stat-card">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3"
                     style="background:#f0fdf4;">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        <path d="M3.5 9Q12 5.5 20.5 9M3.5 15Q12 18.5 20.5 15" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                    </svg>
                </div>
                <p class="text-3xl font-black text-green-700 leading-none">{{ $stats['disponibles'] }}</p>
                <p class="text-sm font-semibold text-gray-700 mt-1.5">Cupos disponibles hoy</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ today()->format('d/m/Y') }} · Horas libres y futuras</p>
            </div>
            <div class="stat-card">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3"
                     style="background:#fffbeb;">
                    <svg class="w-5 h-5" fill="none" stroke="#b45309" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <p class="text-3xl font-black text-gray-900 leading-none">{{ $stats['mis_reservas'] }}</p>
                <p class="text-sm font-semibold text-gray-700 mt-1.5">Mis reservas</p>
                <p class="text-xs text-gray-400 mt-0.5">historial completo</p>
            </div>
        </div>

        {{-- Acciones cliente --}}
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">¿Qué deseas hacer?</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('reservas.disponibles') }}"
               class="card p-7 flex items-start gap-5 hover:shadow-lg transition-all duration-200 group border-l-4"
               style="border-left-color:#15803d;">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-110"
                     style="background:#f0fdf4;">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        <path d="M3.5 9Q12 5.5 20.5 9M3.5 15Q12 18.5 20.5 15" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div>
                    <p class="font-extrabold text-gray-900 text-lg leading-tight">Reservar una cancha</p>
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">Consulta los cupos disponibles y elige un método de pago habilitado por el club.</p>
                    <p class="mt-3 text-sm font-bold text-green-600 group-hover:text-green-700 flex items-center gap-1">
                        Ver canchas disponibles
                        <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </p>
                </div>
            </a>

            <a href="{{ route('reservas.index') }}"
               class="card p-7 flex items-start gap-5 hover:shadow-lg transition-all duration-200 group border-l-4"
               style="border-left-color:#b45309;">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-110"
                     style="background:#fffbeb;">
                    <svg class="w-6 h-6" fill="none" stroke="#b45309" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <p class="font-extrabold text-gray-900 text-lg leading-tight">Mis reservas</p>
                    <p class="text-sm text-gray-400 mt-1 leading-relaxed">Consulta tus tickets digitales, el estado de tus pagos y descarga comprobantes en PDF.</p>
                    <p class="mt-3 text-sm font-bold text-amber-600 group-hover:text-amber-700 flex items-center gap-1">
                        Ver historial
                        <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </p>
                </div>
            </a>
        </div>
    </div>
    @endif
</x-app-layout>
