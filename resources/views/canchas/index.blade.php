<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-300 text-xs font-semibold uppercase tracking-widest mb-0.5">Administración</p>
                <h1 class="text-2xl font-black text-white">Canchas</h1>
            </div>
            <a href="{{ route('canchas.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva cancha
            </a>
        </div>
    </x-slot>

    {{-- Contenido de la página --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @include('partials.errores')

        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-500">{{ $canchas->total() }} canchas registradas</p>
            </div>
            <table class="w-full text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="text-left px-6 py-3.5">Cancha</th>
                        <th class="text-left px-6 py-3.5">Superficie</th>
                        <th class="text-left px-6 py-3.5">Modalidad</th>
                        <th class="text-left px-6 py-3.5">Luz</th>
                        <th class="text-left px-6 py-3.5">Estado</th>
                        <th class="text-left px-6 py-3.5">Horarios</th>
                        <th class="text-right px-6 py-3.5">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($canchas as $cancha)
                        <tr class="{{ $cancha->estado_mantenimiento === 'en_mantenimiento' ? 'bg-amber-50/40' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg overflow-hidden shrink-0 border border-gray-100">
                                        <img src="{{ $cancha->imagenUrl() }}" alt="{{ $cancha->nombre }}"
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900">{{ $cancha->nombre }}</span>
                                        @if($cancha->estado_mantenimiento === 'en_mantenimiento' && $cancha->motivo_mantenimiento)
                                            <p class="text-xs text-amber-600 mt-0.5">{{ Str::limit($cancha->motivo_mantenimiento, 35) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $cancha->tipo_superficie }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $cancha->modalidad ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if($cancha->iluminacion)
                                    <span class="badge badge-blue text-xs">Con luz</span>
                                @else
                                    <span class="badge badge-gray text-xs">Sin luz</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($cancha->estado_mantenimiento === 'operativa')
                                    <span class="badge badge-green">Operativa</span>
                                @else
                                    <div>
                                        <span class="badge badge-yellow">Mantenimiento</span>
                                        @if($cancha->inicio_mantenimiento)
                                            <p class="text-xs text-amber-600 mt-0.5">
                                                Desde {{ $cancha->inicio_mantenimiento->format('d/m/Y H:i') }}
                                            </p>
                                        @endif
                                        @if($cancha->fin_mantenimiento)
                                            <p class="text-xs text-amber-600 mt-0.5">
                                                Hasta {{ $cancha->fin_mantenimiento->format('d/m/Y H:i') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-500 font-medium">{{ $cancha->horarios_count }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    <a href="{{ route('canchas.edit', $cancha) }}" class="btn-outline-sm text-xs py-1.5 px-3">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('canchas.destroy', $cancha) }}"
                                          onsubmit="return confirm('¿Eliminar la cancha {{ addslashes($cancha->nombre) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger text-xs">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="w-12 h-12 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background:#f0fdf4;">
                                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <p class="text-gray-400 font-medium">No hay canchas registradas.</p>
                                <a href="{{ route('canchas.create') }}" class="mt-3 inline-flex text-sm text-green-600 font-semibold hover:underline">
                                    Crear la primera cancha →
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $canchas->links() }}</div>
    </div>
</x-app-layout>
