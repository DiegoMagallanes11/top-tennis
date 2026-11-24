<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-300 text-xs font-semibold uppercase tracking-widest mb-0.5">Administración</p>
                <h1 class="text-2xl font-black text-white">Calendario</h1>
            </div>
            <a href="{{ route('reservas.crearManual') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Reserva manual
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Controles: filtro por cancha + leyenda --}}
        <div class="card p-4 mb-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <label for="filtroCancha" class="form-label mb-0">Cancha</label>
                <select id="filtroCancha" class="form-input" style="width:auto; min-width:180px;">
                    <option value="">Todas</option>
                    @foreach($canchas as $c)
                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-gray-600">
                <span class="flex items-center gap-1.5"><span style="width:12px;height:12px;border-radius:3px;background:#16a34a;display:inline-block;"></span> Libre</span>
                <span class="flex items-center gap-1.5"><span style="width:12px;height:12px;border-radius:3px;background:#dc2626;display:inline-block;"></span> Reservado (pagado)</span>
                <span class="flex items-center gap-1.5"><span style="width:12px;height:12px;border-radius:3px;background:#d97706;display:inline-block;"></span> Pendiente de pago</span>
                <span class="flex items-center gap-1.5"><span style="width:12px;height:12px;border-radius:3px;background:#9ca3af;display:inline-block;"></span> Mantenimiento</span>
            </div>
        </div>

        <div class="card p-4">
            <p id="calendarioEstado" role="status" aria-live="polite" class="text-sm text-gray-600 mb-3">Cargando disponibilidad…</p>
            <div id="calendario"></div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-3">El calendario se actualiza automáticamente cada 30 segundos.</p>
    </div>

    {{-- FullCalendar (gratuito) vía CDN --}}
    
    
    <script>
        document.addEventListener('tt:calendar-ready', function () {
            var el = document.getElementById('calendario');
            var filtro = document.getElementById('filtroCancha');
            if (!el || typeof FullCalendar === 'undefined') { return; }

            var calendar = new FullCalendar.Calendar(el, {
                initialView: window.innerWidth < 640 ? 'timeGridDay' : 'timeGridWeek',
                locale: 'es',
                firstDay: 1,
                nowIndicator: true,
                allDaySlot: false,
                slotMinTime: '06:00:00',
                slotMaxTime: '23:00:00',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay,dayGridMonth'
                },
                events: function (info, success, failure) {
                    document.getElementById('calendarioEstado').textContent = 'Actualizando disponibilidad…';
                    var url = new URL('{{ route('calendario.datos') }}', window.location.origin);
                    url.searchParams.set('start', info.startStr);
                    url.searchParams.set('end', info.endStr);
                    if (filtro.value) { url.searchParams.set('cancha', filtro.value); }

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { if (!r.ok) throw new Error('No se pudo consultar la disponibilidad'); return r.json(); })
                        .then(function (data) { document.getElementById('calendarioEstado').textContent = data.length ? 'Disponibilidad actualizada.' : 'No hay horarios publicados en este rango. Crea horarios desde administración.'; success(data); })
                        .catch(function (e) { document.getElementById('calendarioEstado').textContent = 'No se pudo actualizar. Revisa la conexión y vuelve a elegir la fecha para reintentar.'; failure(e); });
                },
                eventClick: function (info) {
                    var estado = info.event.extendedProps.estado;
                    if (estado === 'disponible') {
                        if (confirm('¿Registrar una reserva manual en este horario?')) {
                            window.location = @json(route('reservas.crearManual'));
                        }
                    } else {
                        if (info.event.extendedProps.ticket) { window.location = info.event.extendedProps.ticket; }
                    }
                }
            });

            calendar.render();

            filtro.addEventListener('change', function () { calendar.refetchEvents(); });
            // Auto-refresco (polling) cada 30 s para ver cambios "en tiempo real".
            setInterval(function () { calendar.refetchEvents(); }, 30000);
        });
    </script><style>.fc-toolbar{flex-wrap:wrap;gap:8px}.fc .fc-toolbar-title{font-size:1.15rem}</style>
</x-app-layout>
