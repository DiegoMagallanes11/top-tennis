<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Top Tennis') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --green:      #16a34a;
            --green-600:  #15803d;
            --green-dark: #166534;
            --green-xd:   #14532d;
            --green-950:  #0d3d22;
            --lime:       #a3e635;
            --ink:        #0f172a;
            --muted:      #64748b;
            --line:       #e6ebe6;
            --surface:    #ffffff;
            --bg:         #f3f6f3;
            --radius:     16px;
            --shadow-sm:  0 1px 2px rgba(16,40,24,.06), 0 1px 3px rgba(16,40,24,.05);
            --shadow-md:  0 4px 14px rgba(16,40,24,.08), 0 2px 6px rgba(16,40,24,.04);
            --shadow-lg:  0 18px 40px rgba(16,40,24,.14);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background-color: var(--bg);
            background-image:
                radial-gradient(1100px 460px at 100% -8%, rgba(22,163,74,.08), transparent 60%),
                radial-gradient(900px 420px at -10% 4%, rgba(163,230,53,.10), transparent 55%);
            background-attachment: fixed;
            -webkit-font-smoothing: antialiased;
        }
        [x-cloak] { display: none !important; }
        h1, h2, h3 { letter-spacing: -0.02em; }

        /* Botones */
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            background: linear-gradient(135deg, #16a34a, #12813c);
            color: #fff; font-weight: 700; letter-spacing: -0.01em;
            padding: .6rem 1.4rem; border-radius: 9999px;
            font-size: .875rem; border: none; cursor: pointer;
            text-decoration: none; white-space: nowrap;
            transition: transform .15s ease, box-shadow .18s ease, filter .18s ease;
            box-shadow: 0 6px 16px rgba(22,163,74,.28);
        }
        .btn-primary:hover { transform: translateY(-1.5px); box-shadow: 0 10px 24px rgba(22,163,74,.36); filter: brightness(1.04); }
        .btn-primary:active { transform: translateY(0); box-shadow: 0 3px 10px rgba(22,163,74,.3); }

        .btn-lg { padding: .8rem 1.9rem; font-size: .95rem; }

        .btn-outline-sm {
            display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
            border: 1.5px solid #d6ddd6; color: #3f4a40; font-weight: 600;
            padding: .5rem 1.05rem; border-radius: 9999px; font-size: .8rem;
            text-decoration: none; background: #fff; cursor: pointer; white-space: nowrap;
            transition: border-color .16s, color .16s, box-shadow .16s, background .16s, transform .12s;
        }
        .btn-outline-sm:hover { border-color: var(--green); color: var(--green-600); box-shadow: 0 4px 12px rgba(22,163,74,.12); transform: translateY(-1px); }

        .btn-danger {
            display: inline-flex; align-items: center; gap: .3rem;
            color: #dc2626; font-size: .8rem; font-weight: 600;
            background: none; border: none; cursor: pointer; padding: .5rem .65rem;
            border-radius: 9999px; text-decoration: none; transition: color .15s, background .15s;
        }
        .btn-danger:hover { color: #991b1b; background: #fef2f2; }

        /* Tarjetas */
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
        }
        .card-hover { transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: #d7e3d7; }

        /* Formularios */
        .form-input {
            width: 100%;
            padding: .7rem 1rem;
            border: 1.5px solid #e2e8e2;
            border-radius: 12px;
            font-size: .95rem;
            color: var(--ink);
            background: #fff;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
        }
        .form-input:focus { border-color: var(--green); box-shadow: 0 0 0 4px rgba(22,163,74,.12); }
        .form-input:disabled { background: #f8faf8; color: #9aa4a0; }
        .form-label { display: block; font-size: .8rem; font-weight: 700; color: #374151; margin-bottom: .4rem; letter-spacing: -0.01em; }

        /* Badges / pills de estado */
        .badge {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .25rem .7rem; border-radius: 9999px;
            font-size: .72rem; font-weight: 700; letter-spacing: .01em; line-height: 1;
        }
        .badge::before { content: ""; width: 6px; height: 6px; border-radius: 9999px; background: currentColor; opacity: .85; }
        .badge-plain::before { display: none; }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #b45309; }
        .badge-gray   { background: #eef2ee; color: #475569; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }

        /* Icono contenedor */
        .icon-box { display: flex; align-items: center; justify-content: center; border-radius: 14px; }

        /* Tablas */
        .table-header { background: #f7faf7; }
        th { font-size: .68rem; font-weight: 800; color: #6b7a70; text-transform: uppercase; letter-spacing: .08em; }
        tbody tr { border-bottom: 1px solid #f1f4f1; transition: background .12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover td { background: #f8fbf8; }

        /* Encabezado de pagina */
        .page-header {
            position: relative; overflow: hidden;
            background:
                radial-gradient(600px 200px at 88% -40%, rgba(163,230,53,.18), transparent 60%),
                linear-gradient(120deg, #0b3a20 0%, #145230 55%, #166534 100%);
            padding: 1.6rem 0;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .page-header::after {
            content: ""; position: absolute; inset: 0; pointer-events: none; opacity: .5;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 34px 34px;
            -webkit-mask-image: linear-gradient(90deg, transparent, #000 40%);
        }
        .page-header > * { position: relative; z-index: 1; }

        /* Mensajes flash */
        .flash-success { background:#f0fdf4; border-color:#86efac; color:#15803d; }
        .flash-error   { background:#fef2f2; border-color:#fca5a5; color:#dc2626; }
        .flash-warning { background:#fffbeb; border-color:#fcd34d; color:#b45309; }

        /* Stat cards */
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--line);
            padding: 1.4rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow .2s, transform .2s, border-color .2s;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: #d7e3d7; }

        /* Etiqueta de seccion */
        .section-label { font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #8a978c; }

        /* Scrollbar sutil */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cfd8cf; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #a9b5a9; }

        /* Animacion de entrada suave */
        @keyframes ttfade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .tt-in { animation: ttfade .35s ease both; }
    </style>
</head>
<body class="font-sans antialiased">
    @if(app()->environment('testing'))<p role="status" class="p-2 text-center bg-amber-100 text-amber-900">Demostración local · Datos de prueba · Sin cobros ni envíos reales</p>@endif

    @include('layouts.navigation')

    {{-- Page Header --}}
    @isset($header)
        <div class="page-header">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </div>
    @endisset

    {{-- Flash Messages --}}
    @foreach([
        'success' => ['flash-success', '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>'],
        'error'   => ['flash-error',   '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>'],
        'warning' => ['flash-warning', '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>'],
    ] as $type => [$classes, $icon])
        @if(session($type))
            <div x-data="{ show: true }" x-show="show"
                 x-init="setTimeout(() => show = false, {{ $type === 'error' ? 6000 : 4000 }})"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="flex items-center justify-between border rounded-xl px-4 py-3 {{ $classes }}" style="box-shadow: var(--shadow-sm);">
                    <div class="flex items-center gap-2.5">
                        {!! $icon !!}
                        <span class="font-semibold text-sm">{{ session($type) }}</span>
                    </div>
                    <button @click="show = false" class="ml-4 opacity-50 hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endif
    @endforeach

    <main class="pb-16 tt-in">
        @if(auth()->user()?->esAdmin())
            <div class="max-w-7xl mx-auto px-4 pt-3 flex flex-wrap gap-3"><a class="btn-outline-sm" href="{{ route('club.edit') }}">Reglas del club</a><a class="btn-outline-sm" href="{{ route('club.auditoria') }}">Registro de acciones</a></div>
        @endif
        {{ $slot }}
    </main>

    <script>
    document.addEventListener('submit', function(event) {
        const form = event.target;
        if (event.defaultPrevented || form.method.toLowerCase() === 'get') return;
        if (form.dataset.pending === 'true') { event.preventDefault(); return; }
        form.dataset.pending = 'true'; form.setAttribute('aria-busy', 'true');
        const button = event.submitter;
        if (button) { button.disabled = true; button.dataset.original = button.innerHTML; button.textContent = 'Guardando…'; }
    });
    window.addEventListener('pageshow', function() {
        document.querySelectorAll('form[data-pending]').forEach(function(form) {
            delete form.dataset.pending; form.removeAttribute('aria-busy');
            form.querySelectorAll('button[data-original]').forEach(function(button) { button.disabled = false; button.innerHTML = button.dataset.original; });
        });
    });
    </script>
    <style>:focus-visible { outline:3px solid #15803d; outline-offset:3px; } button:disabled { opacity:.65; cursor:wait; } @media(prefers-reduced-motion:reduce){.tt-in{animation:none}}</style>
</body>
</html>
