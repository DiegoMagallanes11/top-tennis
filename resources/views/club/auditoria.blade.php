<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-black text-white">Registro de acciones y avisos</h1></x-slot>
    <div class="max-w-6xl mx-auto p-4 sm:p-8 space-y-6">
        <p class="text-sm text-gray-600">Historial de operaciones del club. Cada registro indica fecha, responsable y detalle disponible. Las versiones del código se documentan por separado en el historial del proyecto.</p>
        <div class="card overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="text-left p-4 font-bold">Acciones registradas · {{ config('app.timezone') }}</caption>
                <thead><tr><th class="p-3 text-left">Fecha</th><th class="p-3 text-left">Usuario</th><th class="p-3 text-left">Acción</th><th class="p-3 text-left">Reserva</th><th class="p-3 text-left">Detalle</th></tr></thead>
                <tbody>
                    @forelse($eventos as $evento)
                        <tr class="border-t align-top">
                            <td class="p-3 whitespace-nowrap">{{ $evento->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-3">{{ $evento->actor?->name ?? 'Sistema' }}</td>
                            <td class="p-3">{{ str_replace('_', ' ', $evento->accion) }}</td>
                            <td class="p-3">
                                @if($evento->reserva)<a class="text-green-700 font-bold" href="{{ route('reservas.ticket', $evento->reserva) }}">{{ $evento->reserva->codigo_validacion }}</a>@else — @endif
                            </td>
                            <td class="p-3">
                                @if($evento->datos)
                                    <details><summary class="cursor-pointer font-bold text-green-700 py-1">Ver detalle</summary><pre class="mt-2 max-w-md whitespace-pre-wrap break-words text-xs bg-gray-50 p-3 rounded-lg">{{ json_encode($evento->datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
                                @else <span class="text-gray-500">Sin detalle adicional</span> @endif
                            </td>
                        </tr>
                    @empty <tr><td colspan="5" class="p-5">Aún no hay acciones registradas.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        {{ $eventos->links() }}
        <div class="card overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="text-left p-4 font-bold">Avisos: “enviado” indica aceptación por el servidor de correo</caption>
                <thead><tr><th class="p-3 text-left">Evento</th><th class="p-3 text-left">Destinatario</th><th class="p-3 text-left">Estado</th><th class="p-3 text-left">Intentos</th></tr></thead>
                <tbody>
                    @forelse($notificaciones as $aviso)<tr class="border-t"><td class="p-3">{{ $aviso->evento }}</td><td class="p-3">{{ $aviso->destinatario }}</td><td class="p-3">{{ $aviso->estado }}</td><td class="p-3">{{ $aviso->intentos }}</td></tr>@empty<tr><td colspan="4" class="p-5">No hay avisos registrados.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $notificaciones->links() }}
    </div>
</x-app-layout>
