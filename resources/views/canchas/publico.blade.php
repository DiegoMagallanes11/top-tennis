<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Canchas — Top Tennis Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; }
        .court-card { background: #fff; border-radius: 1.1rem; overflow: hidden; box-shadow: 0 2px 10px rgba(16,40,24,.07); border: 1px solid #eef2ee; transition: transform .2s ease, box-shadow .2s ease; }
        .court-card:hover { transform: translateY(-4px); box-shadow: 0 20px 42px rgba(16,40,24,.16); }
        .court-card-img { height: 180px; position: relative; display: flex; align-items: center; justify-content: center; }
        .court-type-badge { position: absolute; top: .6rem; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,.45); color: #fff; font-size: .7rem; font-weight: 600; padding: .2rem .65rem; border-radius: 9999px; backdrop-filter: blur(4px); }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <svg width="34" height="34" viewBox="0 0 56 56" fill="none">
                    <circle cx="28" cy="28" r="26" fill="#dcfce7" stroke="#22c55e" stroke-width="3"/>
                    <path d="M6 22 Q28 13 50 22" stroke="#22c55e" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <path d="M6 34 Q28 43 50 34" stroke="#22c55e" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                </svg>
                <span class="font-bold text-sm tracking-widest uppercase text-gray-900">Top Tennis Digital</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('welcome') }}" class="text-sm text-gray-600 hover:text-green-600">Inicio</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-green-600">Mi Panel</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-green-600">Iniciar Sesión</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="max-w-6xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Nuestras Canchas</h1>
            <p class="text-gray-500 text-lg">Conoce las canchas disponibles en nuestro club</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($canchas as $cancha)
                <div class="court-card">
                    <div class="court-card-img" style="background:url('{{ $cancha->imagenUrl() }}') center/cover;">
                        <span class="court-type-badge">{{ $cancha->tipo_superficie }}</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-gray-900 text-lg">{{ $cancha->nombre }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $cancha->modalidad ?? 'Singles' }} · Superficie {{ $cancha->tipo_superficie }}</p>
                        <div class="flex items-center gap-2 mt-3">
                            @if($cancha->iluminacion)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 px-2 py-1 rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                                    Iluminación
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 bg-blue-50 px-2 py-1 rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Operativa
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-400 py-16">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="text-lg">No hay canchas disponibles en este momento.</p>
                </div>
            @endforelse
        </div>

        <div class="text-center mt-12">
            @auth
                <a href="{{ route('reservas.disponibles') }}" class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold px-8 py-3 rounded-full transition">
                    Reservar ahora
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold px-8 py-3 rounded-full transition">
                    Registrarse gratis
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-400 text-sm py-8 px-6 text-center">
        <div class="flex items-center justify-center gap-2 mb-2">
            <svg width="22" height="22" viewBox="0 0 56 56" fill="none">
                <circle cx="28" cy="28" r="26" fill="none" stroke="#4ade80" stroke-width="3"/>
                <path d="M6 22 Q28 13 50 22" stroke="#4ade80" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                <path d="M6 34 Q28 43 50 34" stroke="#4ade80" stroke-width="2.5" fill="none" stroke-linecap="round"/>
            </svg>
            <span class="font-bold text-white text-sm tracking-widest uppercase">Top Tennis Digital</span>
        </div>
        <p>© {{ date('Y') }} Top Tennis Digital. Todos los derechos reservados.</p>
    </footer>

</body>
</html>
