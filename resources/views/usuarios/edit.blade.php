<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-green-300 text-xs font-semibold uppercase tracking-widest mb-0.5">Administración</p>
            <h1 class="text-2xl font-black text-white">Editar usuario</h1>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('partials.errores')
        @include('usuarios._form', ['accion' => route('usuarios.update', $usuario)])
    </div>
</x-app-layout>
