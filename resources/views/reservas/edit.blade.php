<x-app-layout>
<x-slot name="header"><h1 class="text-2xl font-black text-white">Cambiar horario · {{ $reserva->codigo_validacion }}</h1></x-slot>
<div class="max-w-2xl mx-auto p-4 sm:p-8">@include('partials.errores')
<div class="card p-6"><p class="mb-4 text-gray-600">Puedes cambiar o cancelar hasta {{ \App\Models\ClubSetting::reglas()['cancelacion_horas'] }} horas antes. Si ya pagaste, el nuevo horario debe tener el mismo precio.</p>
@if($reserva->activa() && $horarios->isNotEmpty())
<form method="POST" action="{{ route('reservas.update', $reserva) }}" class="space-y-5">@csrf @method('PATCH')
<label for="horario_id" class="form-label">Nuevo horario disponible</label><select name="horario_id" id="horario_id" required class="form-input"><option value="">Selecciona un horario</option>
@foreach($horarios as $h)<option value="{{ $h->id }}" @selected(old('horario_id') == $h->id)>{{ $h->hora_inicio->format('d/m/Y H:i') }} – {{ $h->hora_fin->format('H:i') }} · {{ $h->cancha->nombre }} · S/ {{ $h->tarifa->precio }}</option>@endforeach</select>
<button type="submit" class="btn-primary">Guardar cambio</button></form>
@else<p role="status">No hay horarios disponibles para cambiar esta reserva. Comunícate con el club.</p>@endif
<a href="{{ route('reservas.index') }}" class="btn-outline-sm mt-4">Volver a mis reservas</a></div></div>
</x-app-layout>
