@php
    $codigo = $estado['codigo'];
    // Colores del banner según el estado.
    $tema = match ($codigo) {
        'valida'    => ['bg' => '#16a34a', 'chip' => '#dcfce7', 'txt' => '#14532d', 'icon' => 'check'],
        'pendiente' => ['bg' => '#d97706', 'chip' => '#fef3c7', 'txt' => '#92400e', 'icon' => 'clock'],
        'usada'     => ['bg' => '#6b7280', 'chip' => '#f3f4f6', 'txt' => '#374151', 'icon' => 'used'],
        default     => ['bg' => '#dc2626', 'chip' => '#fee2e2', 'txt' => '#991b1b', 'icon' => 'x'],
    };
    $h = $reserva?->horario;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de reserva · Top Tennis</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:ui-sans-serif,system-ui,sans-serif;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background:#f1f5f0;">

    <div class="w-full max-w-md bg-white rounded-3xl overflow-hidden shadow-xl">

        {{-- Banner de estado --}}
        <div class="px-6 py-8 text-center" style="background:{{ $tema['bg'] }};">
            <div class="mx-auto mb-3 flex items-center justify-center w-16 h-16 rounded-full bg-white/20">
                @switch($tema['icon'])
                    @case('check')
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @break
                    @case('clock')
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2.5"/><path stroke-linecap="round" stroke-width="2.5" d="M12 7v5l3 3"/></svg>
                        @break
                    @case('used')
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @break
                    @default
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                @endswitch
            </div>
            <h1 class="text-white text-2xl font-extrabold">{{ $estado['titulo'] }}</h1>
            <p class="text-white/90 text-sm mt-1">{{ $estado['mensaje'] }}</p>
        </div>

        {{-- Detalle de la reserva --}}
        <div class="p-6">
            @if($reserva)
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Código</span><span class="font-bold text-gray-900">{{ $reserva->codigo_validacion }}</span></div>
                    @if($esStaff)<div class="flex justify-between"><span class="text-gray-500">Cliente</span><span class="font-bold text-gray-900">{{ $reserva->user?->name ?? '-' }}</span></div>@endif
                    <div class="flex justify-between"><span class="text-gray-500">Cancha</span><span class="font-bold text-gray-900">{{ optional($h?->cancha)->nombre ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Fecha</span><span class="font-bold text-gray-900">{{ optional($h?->hora_inicio)->format('d/m/Y') ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Horario</span><span class="font-bold text-gray-900">{{ optional($h?->hora_inicio)->format('H:i') }} - {{ optional($h?->hora_fin)->format('H:i') }}</span></div>
                    <div class="flex justify-between items-center"><span class="text-gray-500">Pago</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:{{ $tema['chip'] }}; color:{{ $tema['txt'] }};">{{ ucfirst($reserva->estado_pago) }}</span>
                    </div>
                </div>

                {{-- Flash / errores del "marcar ingreso" --}}
                @if(session('success'))
                    <div class="mt-5 rounded-xl px-4 py-3 text-sm font-semibold" style="background:#f0fdf4;color:#15803d;border:1px solid #86efac;">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mt-5 rounded-xl px-4 py-3 text-sm font-semibold" style="background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;">{{ $errors->first() }}</div>
                @endif

                {{-- Acción del admin: registrar ingreso --}}
                @if($esStaff && $estado['ok'] && ! $reserva->estaUsada())
                    <form method="POST" action="{{ route('validacion.usar', $token) }}" class="mt-6">
                        @csrf
                        <button type="submit" class="w-full py-3 rounded-full text-white font-bold" style="background:#15803d;">
                            Registrar ingreso
                        </button>
                    </form>
                    <p class="text-center text-xs text-gray-400 mt-2">El personal del club puede registrar el ingreso.</p>
                @elseif($esStaff && $reserva->estaUsada())
                    <p class="mt-6 text-center text-sm text-gray-500">Ingreso ya registrado el {{ $reserva->usado_at->format('d/m/Y H:i') }}.</p>
                @endif
            @else
                <p class="text-center text-gray-500 text-sm">Verifica que el código QR sea de un ticket de Top Tennis.</p>
            @endif

            <div class="mt-8 flex items-center justify-center gap-2 text-gray-400">
                <svg width="20" height="20" viewBox="0 0 56 56" fill="none"><circle cx="28" cy="28" r="25" fill="none" stroke="#16a34a" stroke-width="3"/><path d="M7 22 Q28 13 49 22" stroke="#16a34a" stroke-width="2.5" fill="none" stroke-linecap="round"/><path d="M7 34 Q28 43 49 34" stroke="#16a34a" stroke-width="2.5" fill="none" stroke-linecap="round"/></svg>
                <span class="text-xs font-bold tracking-widest uppercase">Top Tennis Digital</span>
            </div>
        </div>
    </div>

</body>
</html>
