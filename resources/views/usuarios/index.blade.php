<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-300 text-xs font-semibold uppercase tracking-widest mb-0.5">Administración</p>
                <h1 class="text-2xl font-black text-white">Usuarios</h1>
            </div>
            <a href="{{ route('usuarios.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo usuario
            </a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @include('partials.errores')

        {{-- Filtros --}}
        <form method="GET" class="card p-4 mb-5 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="form-label" for="buscar">Buscar</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" class="form-input" placeholder="Nombre o correo">
            </div>
            <div>
                <label class="form-label" for="rol">Rol</label>
                <select id="rol" name="rol" class="form-input" style="min-width:170px;">
                    <option value="">Todos</option>
                    <option value="admin"         @selected(request('rol')==='admin')>Administrador</option>
                    <option value="recepcionista" @selected(request('rol')==='recepcionista')>Recepcionista</option>
                    <option value="cliente"       @selected(request('rol')==='cliente')>Cliente</option>
                </select>
            </div>
            <button class="btn-primary" type="submit">Filtrar</button>
            @if(request('buscar') || request('rol'))
                <a href="{{ route('usuarios.index') }}" class="btn-outline-sm">Limpiar</a>
            @endif
        </form>

        {{-- Tabla --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="text-left px-6 py-3.5">Nombre</th>
                            <th class="text-left px-6 py-3.5">Correo</th>
                            <th class="text-left px-6 py-3.5">Rol</th>
                            <th class="text-left px-6 py-3.5">Estado</th>
                            <th class="text-center px-6 py-3.5">Reservas</th>
                            <th class="text-right px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuarios as $u)
                            @php
                                $esSelf = $u->id === auth()->id();
                                $rolBadge = ['admin'=>'badge-blue','recepcionista'=>'badge-yellow','cliente'=>'badge-gray'][$u->rol->value] ?? 'badge-gray';
                            @endphp
                            <tr>
                                <td class="px-6 py-3.5">
                                    <span class="font-semibold text-gray-900">{{ $u->name }}</span>
                                    @if($esSelf)<span class="text-xs text-gray-400">(tú)</span>@endif
                                    @if($u->telefono)<p class="text-xs text-gray-400">{{ $u->telefono }}</p>@endif
                                </td>
                                <td class="px-6 py-3.5 text-gray-600">{{ $u->email }}</td>
                                <td class="px-6 py-3.5"><span class="badge {{ $rolBadge }}">{{ $u->rol->etiqueta() }}</span></td>
                                <td class="px-6 py-3.5">
                                    <span class="badge {{ $u->activo ? 'badge-green' : 'badge-red' }}">{{ $u->activo ? 'Activa' : 'Inactiva' }}</span>
                                </td>
                                <td class="px-6 py-3.5 text-center text-gray-600">{{ $u->reservas_count }}</td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('usuarios.edit', $u) }}" class="btn-outline-sm">Editar</a>
                                        <a href="{{ route('reservas.index', ['cliente' => $u->id]) }}" class="btn-outline-sm">Historial</a>
                                        @unless($esSelf)
                                            <form method="POST" action="{{ route('usuarios.toggleActivo', $u) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn-outline-sm">{{ $u->activo ? 'Desactivar' : 'Activar' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('usuarios.destroy', $u) }}"
                                                  onsubmit="return confirm('¿Eliminar a {{ $u->name }}? Su historial se conserva.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-danger">Eliminar</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-14 text-center text-gray-400">No hay usuarios que coincidan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">{{ $usuarios->links() }}</div>
    </div>
</x-app-layout>
