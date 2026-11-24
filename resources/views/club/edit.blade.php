<x-app-layout>
<x-slot name="header"><h1 class="text-2xl font-black text-white">Reglas del club</h1></x-slot>
<div class="max-w-3xl mx-auto p-4 sm:p-8">@include('partials.errores')
<p class="mb-5 text-gray-600">Zona horaria: {{ config('app.timezone') }}. Las reglas se aplican a nuevas reservas y cambios de horario. Cada cancha admite una reserva a la vez.</p>
<form method="POST" action="{{ route('club.update') }}" class="card p-6 space-y-5">@csrf @method('PATCH')
{{-- Publicación continua: el administrador define reglas y tarifas una sola vez. --}}
<fieldset class="space-y-3 border-b border-gray-200 pb-5">
    <legend class="form-label">Horarios automáticos</legend>
    <input type="hidden" name="generacion_automatica" value="0">
    <label class="flex items-center gap-2"><input type="checkbox" name="generacion_automatica" value="1" @checked(old('generacion_automatica', $reglas['generacion_automatica']))> Mantener los horarios disponibles cada día</label>
    <p class="text-sm text-gray-500">El sistema agrega fechas automáticamente al entrar a reservas o al panel. Respeta días cerrados, festivos, descanso y mantenimiento. Conserva reservas y horarios existentes, incluidos los retirados por administración.</p>
    <div><label for="dias_publicacion" class="form-label">Días por adelantado</label><input id="dias_publicacion" type="number" min="1" max="90" name="dias_publicacion" class="form-input" required value="{{ old('dias_publicacion', $reglas['dias_publicacion']) }}"><p class="text-xs text-gray-500 mt-1">Incluye hoy y respeta el límite de anticipación del club. El cliente ve un día a la vez.</p></div>
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach(['tarifa_dia_id' => 'Tarifa antes de las 18:00', 'tarifa_noche_id' => 'Tarifa desde las 18:00'] as $campo => $etiqueta)
            <div><label for="{{ $campo }}" class="form-label">{{ $etiqueta }}</label><select id="{{ $campo }}" name="{{ $campo }}" class="form-input"><option value="">Usar {{ $campo === 'tarifa_dia_id' ? 'Tarifa Día' : 'Tarifa Noche' }} existente</option>@foreach($tarifas as $tarifa)<option value="{{ $tarifa->id }}" @selected(old($campo, $reglas[$campo]) == $tarifa->id)>{{ $tarifa->nombre_tarifa }} · S/ {{ number_format($tarifa->precio, 2) }}</option>@endforeach</select></div>
        @endforeach
    </div>
    <p class="text-xs text-gray-500">Si falta alguna tarifa, el sistema avisa al personal y no inventa precios.</p>
</fieldset>
<fieldset class="space-y-3">
    <legend class="form-label">Horario de atención por día</legend>
    <p class="text-sm text-gray-500">Estos horarios se usan en la home, la disponibilidad y la generación de nuevos horarios. Los días desmarcados más abajo permanecen cerrados.</p>
    @foreach(['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'] as $i => $dia)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end border-b border-gray-100 pb-3">
            <p class="font-bold text-gray-700">{{ $dia }}</p>
            <div><label for="apertura-{{ $i }}" class="form-label">Apertura</label><input id="apertura-{{ $i }}" name="horarios_semana[{{ $i }}][apertura]" type="time" required class="form-input" value="{{ old('horarios_semana.'.$i.'.apertura', $reglas['horarios_semana'][$i]['apertura']) }}"></div>
            <div><label for="cierre-{{ $i }}" class="form-label">Cierre</label><input id="cierre-{{ $i }}" name="horarios_semana[{{ $i }}][cierre]" type="time" required class="form-input" value="{{ old('horarios_semana.'.$i.'.cierre', $reglas['horarios_semana'][$i]['cierre']) }}"></div>
        </div>
    @endforeach
</fieldset>
<p class="text-sm text-gray-500">Horario base y descanso general. El horario de cada día tiene prioridad sobre el horario base.</p>
<div class="grid sm:grid-cols-2 gap-4">
@foreach(['apertura'=>'Apertura','cierre'=>'Cierre','descanso_inicio'=>'Inicio del descanso (opcional)','descanso_fin'=>'Fin del descanso (opcional)'] as $campo=>$etiqueta)
<div><label for="{{ $campo }}" class="form-label">{{ $etiqueta }}</label><input id="{{ $campo }}" name="{{ $campo }}" type="time" class="form-input" value="{{ old($campo, $reglas[$campo]) }}" {{ in_array($campo,['apertura','cierre']) ? 'required' : '' }}></div>
@endforeach
@foreach(['anticipacion_minutos'=>'Anticipación mínima (minutos)','anticipacion_dias'=>'Anticipación máxima (días)','cancelacion_horas'=>'Límite de cambios y cancelación (horas antes)','margen_minutos'=>'Preparación entre reservas (minutos)','duracion_minutos'=>'Duración de nuevos horarios (minutos)'] as $campo=>$etiqueta)
<div><label for="{{ $campo }}" class="form-label">{{ $etiqueta }}</label><input id="{{ $campo }}" name="{{ $campo }}" type="number" min="0" required class="form-input" value="{{ old($campo, $reglas[$campo]) }}"></div>
@endforeach
</div><fieldset><legend class="form-label">Días laborables</legend><div class="flex flex-wrap gap-3 mt-2">
@foreach(['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'] as $i=>$dia)
<label><input type="checkbox" name="dias[]" value="{{ $i }}" @checked(in_array($i, old('dias', $reglas['dias'])))> {{ $dia }}</label>
@endforeach
</div></fieldset><div><label for="festivos" class="form-label">Festivos (una fecha por línea, AAAA-MM-DD)</label><textarea id="festivos" name="festivos" rows="4" class="form-input">{{ old('festivos', implode("\n", $reglas['festivos'])) }}</textarea></div>
<button class="btn-primary" type="submit">Guardar reglas</button><a href="{{ route('club.auditoria') }}" class="btn-outline-sm">Registro de acciones</a>
</form></div>
</x-app-layout>
