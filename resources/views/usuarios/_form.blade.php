@php $esEdicion = $usuario->exists; @endphp
<form method="POST" action="{{ $accion }}" class="card p-6 max-w-2xl">
    @csrf
    @if($esEdicion) @method('PATCH') @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
            <label class="form-label" for="name">Nombre completo</label>
            <input id="name" name="name" class="form-input" value="{{ old('name', $usuario->name) }}" required>
        </div>

        <div>
            <label class="form-label" for="email">Correo</label>
            <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $usuario->email) }}" required>
        </div>

        <div>
            <label class="form-label" for="telefono">Teléfono <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input id="telefono" name="telefono" class="form-input" value="{{ old('telefono', $usuario->telefono) }}">
        </div>

        <div>
            <label class="form-label" for="rol">Rol</label>
            @php $rolActual = old('rol', $usuario->rol?->value ?? 'cliente'); @endphp
            <select id="rol" name="rol" class="form-input" required>
                <option value="cliente"       @selected($rolActual==='cliente')>Cliente</option>
                <option value="recepcionista" @selected($rolActual==='recepcionista')>Recepcionista</option>
                <option value="admin"         @selected($rolActual==='admin')>Administrador</option>
            </select>
        </div>

        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" class="w-4 h-4 rounded"
                       @checked(old('activo', $usuario->activo ?? true))>
                Cuenta activa
            </label>
        </div>

        <div>
            <label class="form-label" for="password">
                {{ $esEdicion ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
            </label>
            <input id="password" name="password" type="password" class="form-input" autocomplete="new-password" {{ $esEdicion ? '' : 'required' }}>
        </div>

        <div>
            <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password">
        </div>
    </div>

    <div class="flex items-center gap-3 mt-7">
        <button type="submit" class="btn-primary">{{ $esEdicion ? 'Guardar cambios' : 'Crear usuario' }}</button>
        <a href="{{ route('usuarios.index') }}" class="btn-outline-sm">Cancelar</a>
    </div>
</form>
