<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Juega en arcilla en Top Tennis Perú. Crea tu cuenta, consulta horarios y solicita tu reserva. Vía Láctea 301, Lima.">
    <meta name="theme-color" content="#14532d">
    <title>Top Tennis Perú · Tu próxima hora en arcilla</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
    {{-- Estilos exclusivos de la home. --}}
    <style>
        .tt-home {
            --green:#16a34a;
            --forest:#14532d;
            --clay:#C1532D;
            --gold:#C9A227;
            --ink:#173027;
            --muted:#56665e;
            background:#fafbf8;
            color:var(--ink);
            font-family:'Figtree',ui-sans-serif,system-ui,sans-serif;
            -webkit-font-smoothing:antialiased;
        }
        .tt-home * {
            box-sizing:border-box;
        }
        .tt-home [x-cloak] {
            display:none!important;
        }
        .tt-home a {
            text-decoration:none;
        }
        .tt-home button {
            font:inherit;
        }
        .tt-home a:focus-visible,.tt-home button:focus-visible {
            outline:3px solid var(--clay);
            outline-offset:5px;
        }
        .tt-home h1,.tt-home h2,.tt-home h3,.tt-home p {
            margin:0;
        }
        .tt-home h1,.tt-home h2,.tt-home h3 {
            text-wrap:balance;
        }
        .tt-home img {
            display:block;
            max-width:100%;
        }
        .tt-home section[id],.tt-home footer[id] {
            scroll-margin-top:105px;
        }
        .tt-container {
            width:min(1160px,calc(100% - 40px));
            margin-inline:auto;
        }
        .tt-icon {
            width:22px;
            height:22px;
            flex-shrink:0;
            fill:none;
            stroke:currentColor;
            stroke-width:1.7;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        .tt-skip {
            position:fixed;
            top:10px;
            left:10px;
            z-index:100;
            transform:translateY(-180%);
            padding:12px 20px;
            background:white;
            color:var(--forest);
            border-radius:10px;
        }
        .tt-skip:focus {
            transform:translateY(0);
        }
        .tt-nav {
            position:sticky;
            top:0;
            z-index:50;
            border-bottom:1px solid #e5eae3;
            background:rgba(255,255,255,.96);
            backdrop-filter:blur(16px);
        }
        .tt-nav-row {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            min-height:80px;
        }
        .tt-brand {
            display:inline-flex;
            align-items:center;
            gap:10px;
            color:var(--ink);
            flex-shrink:0;
        }
        .tt-logo {
            width:38px;
            height:38px;
            color:var(--forest);
        }
        .tt-brand strong {
            display:block;
            font-size:18px;
            font-weight:900;
            letter-spacing:-.6px;
            line-height:1.1;
        }
        .tt-brand small {
            display:block;
            font-size:10px;
            letter-spacing:2px;
            font-weight:700;
            margin-top:5px;
            text-transform:uppercase;
        }
        .tt-desktop-links,.tt-account {
            display:none;
        }
        .tt-nav-actions {
            display:flex;
            align-items:center;
            gap:10px;
        }
        .tt-btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:12px;
            min-height:50px;
            padding:13px 23px;
            border:1px solid transparent;
            border-radius:12px;
            font-size:15px;
            font-weight:800;
            line-height:1.4;
            transition:background .18s,transform .18s;
        }
        .tt-btn-primary {
            background:var(--green);
            color:#fff;
            box-shadow:0 5px 15px #14532d12;
        }
        .tt-btn-primary:hover {
            background:#15803d;
            transform:translateY(-2px);
        }
        .tt-btn-secondary {
            background:white;
            border-color:#d7dfd6;
            color:var(--forest);
        }
        .tt-btn-secondary:hover {
            background:#f0f7ee;
        }
        .tt-nav .tt-btn {
            min-height:44px;
            padding:10px 15px;
            font-size:13px;
        }
        .tt-menu-button {
            display:grid;
            place-items:center;
            width:44px;
            height:44px;
            border:1px solid #d7dfd6;
            border-radius:10px;
            color:var(--forest);
            background:white;
            cursor:pointer;
        }
        .tt-mobile-menu {
            padding:0 0 18px;
            border-top:1px solid #e5eae3;
        }
        .tt-mobile-menu a {
            display:block;
            padding:13px 0;
            color:var(--forest);
            font-weight:700;
        }
        .tt-hero {
            padding:44px 0 46px;
            background:radial-gradient(ellipse at 90% 30%,#f4e5da80,transparent 65%);
        }
        .tt-hero-grid {
            display:grid;
            gap:36px;
            align-items:center;
        }
        .tt-eyebrow {
            display:flex;
            align-items:center;
            gap:9px;
            color:var(--clay);
            font-size:11px;
            letter-spacing:2px;
            text-transform:uppercase;
            font-weight:800;
        }
        .tt-eyebrow::before {
            content:'';
            width:24px;
            height:2px;
            background:var(--gold);
        }
        .tt-hero h1 {
            margin-top:22px;
            font-size:clamp(40px,6.2vw,70px);
            font-weight:900;
            letter-spacing:-2px;
            line-height:1.04;
        }
        .tt-hero h1 span {
            display:block;
            color:var(--clay);
        }
        .tt-hero-copy {
            margin-top:23px!important;
            max-width:440px;
            color:var(--muted);
            font-size:17px;
            line-height:1.75;
        }
        .tt-hero-buttons {
            display:flex;
            flex-wrap:wrap;
            gap:12px;
            margin-top:27px;
        }
        .tt-registration {
            margin-top:14px!important;
            color:var(--muted);
            font-size:12px;
            line-height:1.65;
        }
        .tt-registration a {
            color:var(--forest);
            text-decoration:underline;
            text-underline-offset:3px;
            font-weight:700;
        }
        .tt-availability {
            display:flex;
            align-items:center;
            gap:8px;
            margin-top:28px;
            font-size:12px;
            color:var(--forest);
            font-weight:700;
        }
        .tt-dot {
            width:7px;
            height:7px;
            background:var(--green);
            border-radius:50%;
            box-shadow:0 0 0 4px #16a34a12;
            flex-shrink:0;
        }
        .tt-hero-visual {
            position:relative;
            padding:0 0 18px 12px;
        }
        .tt-hero-visual::before {
            content:'';
            position:absolute;
            inset:20px 12px 0 0;
            border:1px solid #C9A22780;
            border-radius:24px;
        }
        .tt-photo {
            position:relative;
            height:350px;
            overflow:hidden;
            border-radius:20px;
            background:#ad6845;
            box-shadow:0 20px 45px #52291914;
        }
        .tt-photo img {
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .tt-photo::after {
            content:'';
            position:absolute;
            inset:0;
            background:linear-gradient(180deg,#10251a14 25%,#10251aa8 100%);
        }
        .tt-photo-badge {
            position:absolute;
            top:18px;
            left:18px;
            z-index:1;
            display:flex;
            align-items:center;
            gap:7px;
            padding:9px 13px;
            border-radius:8px;
            color:#7f361b;
            background:#fff9f1;
            font-size:12px;
            font-weight:800;
        }
        .tt-photo-caption {
            position:absolute;
            left:23px;
            right:23px;
            bottom:25px;
            z-index:1;
            color:white;
        }
        .tt-photo-caption small {
            display:block;
            font-size:10px;
            letter-spacing:2px;
            text-transform:uppercase;
            color:#f5e3bc;
        }
        .tt-photo-caption strong {
            display:block;
            margin-top:8px;
            font-size:27px;
            letter-spacing:-.6px;
            line-height:1.2;
        }
        .tt-photo-caption p {
            margin-top:8px;
            font-size:13px;
            color:#f3eee8;
        }
        .tt-trust {
            border-block:1px solid #e5eae3;
            background:white;
        }
        .tt-trust-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:22px 18px;
            padding-block:25px;
        }
        .tt-trust-item {
            display:flex;
            align-items:center;
            gap:10px;
            font-size:12px;
            font-weight:700;
            line-height:1.5;
        }
        .tt-trust-item .tt-icon {
            color:var(--clay);
        }
        .tt-section {
            padding-block:65px;
        }
        .tt-heading {
            margin-top:14px!important;
            font-size:clamp(28px,4vw,40px);
            font-weight:900;
            line-height:1.15;
            letter-spacing:-1px;
        }
        .tt-intro {
            max-width:540px;
            margin-top:15px!important;
            font-size:15px;
            line-height:1.75;
            color:var(--muted);
        }
        .tt-features {
            display:grid;
            gap:15px;
            margin-top:30px;
        }
        .tt-feature {
            padding:26px;
            background:white;
            border:1px solid #e4e9e0;
            border-radius:16px;
            box-shadow:0 4px 15px #17302703;
        }
        .tt-feature-icon {
            display:grid;
            place-items:center;
            width:44px;
            height:44px;
            margin-bottom:22px;
            border-radius:12px;
            background:#eef5ea;
            color:var(--forest);
        }
        .tt-feature:first-child .tt-feature-icon {
            background:#faeee6;
            color:var(--clay);
        }
        .tt-feature h3 {
            font-size:17px;
            font-weight:800;
        }
        .tt-feature p {
            margin-top:10px;
            color:var(--muted);
            font-size:14px;
            line-height:1.7;
        }
        .tt-how {
            background:#edf3e9;
            border-block:1px solid #e0e9db;
        }
        .tt-steps {
            display:grid;
            gap:28px;
            margin-top:36px;
        }
        .tt-step {
            display:flex;
            align-items:flex-start;
            gap:18px;
        }
        .tt-step-number {
            display:grid;
            place-items:center;
            width:45px;
            height:45px;
            flex-shrink:0;
            border-radius:50%;
            background:white;
            border:1px solid #d1dec9;
            color:var(--clay);
            font-size:16px;
            font-weight:900;
        }
        .tt-step h3 {
            font-size:18px;
            font-weight:800;
        }
        .tt-step p {
            font-size:14px;
            color:var(--muted);
            line-height:1.7;
            margin-top:8px;
        }
        .tt-how-bottom {
            display:flex;
            flex-direction:column;
            align-items:flex-start;
            gap:18px;
            margin-top:35px;
            padding-top:25px;
            border-top:1px solid #d7e3d1;
        }
        .tt-how-note {
            max-width:650px;
            color:var(--muted);
            font-size:12px;
            line-height:1.8;
        }
        .tt-courts-heading {
            display:flex;
            flex-wrap:wrap;
            align-items:flex-end;
            justify-content:space-between;
            gap:20px;
        }
        .tt-location {
            display:flex;
            align-items:center;
            gap:7px;
            color:var(--muted);
            font-size:13px;
        }
        .tt-courts {
            display:grid;
            gap:22px;
            margin-top:32px;
        }
        .tt-court {
            overflow:hidden;
            background:white;
            border:1px solid #e4e9e0;
            border-radius:17px;
            box-shadow:0 8px 24px #17302706;
        }
        .tt-court-image {
            position:relative;
            height:220px;
            background:#eee8df;
        }
        .tt-court-image img {
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .tt-surface {
            position:absolute;
            top:15px;
            left:15px;
            padding:7px 12px;
            border-radius:7px;
            color:var(--ink);
            background:#ffffffed;
            font-size:12px;
            font-weight:800;
        }
        .tt-court-body {
            padding:23px;
        }
        .tt-court-body h3 {
            font-size:20px;
            font-weight:800;
        }
        .tt-court-meta {
            display:flex;
            flex-wrap:wrap;
            gap:8px 15px;
            margin-top:12px;
            color:var(--muted);
            font-size:12px;
        }
        .tt-court-meta span {
            display:inline-flex;
            align-items:center;
            gap:5px;
        }
        .tt-court-meta .tt-icon {
            width:16px;
            height:16px;
        }
        .tt-court-link {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:23px;
            padding-top:17px;
            border-top:1px solid #e5eae3;
            color:var(--forest);
            font-size:14px;
            font-weight:800;
            min-height:44px;
        }
        .tt-court-link:hover {
            color:#15803d;
            text-decoration:underline;
            text-underline-offset:4px;
        }
        .tt-empty {
            grid-column:1/-1;
            padding:35px;
            border:1px dashed #cdd8c7;
            border-radius:16px;
            color:var(--muted);
            text-align:center;
            line-height:1.8;
        }
        .tt-final {
            padding-bottom:65px;
        }
        .tt-final-card {
            position:relative;
            overflow:hidden;
            padding:36px 25px;
            border-radius:22px;
            background:var(--forest);
            color:white;
        }
        .tt-final-card::after {
            content:'';
            position:absolute;
            width:240px;
            height:300px;
            top:-90px;
            right:-110px;
            border:1px solid #C9A22760;
            transform:rotate(25deg);
            pointer-events:none;
        }
        .tt-final-content {
            position:relative;
            z-index:1;
        }
        .tt-final .tt-eyebrow {
            color:#ead8b5;
        }
        .tt-final h2 {
            margin-top:16px;
            max-width:610px;
            font-size:clamp(30px,4vw,42px);
            line-height:1.13;
            font-weight:900;
            letter-spacing:-1px;
        }
        .tt-final p {
            margin-top:16px;
            color:#deebde;
            font-size:14px;
            line-height:1.7;
        }
        .tt-final .tt-btn {
            margin-top:25px;
        }
        .tt-footer {
            padding-top:45px;
            background:white;
            border-top:1px solid #e5eae3;
        }
        .tt-footer-grid {
            display:grid;
            gap:32px;
            padding-bottom:36px;
        }
        .tt-footer h2 {
            margin-bottom:15px;
            font-size:13px;
            text-transform:uppercase;
            letter-spacing:1px;
            font-weight:800;
        }
        .tt-footer p,.tt-footer address,.tt-footer a:not(.tt-brand) {
            font-size:14px;
            color:var(--muted);
            line-height:1.9;
            font-style:normal;
        }
        .tt-footer a:not(.tt-brand):hover {
            color:var(--forest);
            text-decoration:underline;
        }
        .tt-footer-intro {
            max-width:250px;
            margin-top:16px!important;
        }
        .tt-contact-link {
            display:flex;
            align-items:center;
            gap:8px;
            width:fit-content;
            margin-top:8px;
        }
        .tt-footer-bottom {
            padding-block:21px;
            border-top:1px solid #e5eae3;
            display:flex;
            flex-wrap:wrap;
            justify-content:space-between;
            gap:8px;
        }
        .tt-footer-bottom p {
            font-size:11px;
        }
        @media(min-width:640px) {
            .tt-container {
                width:min(1160px,calc(100% - 64px));
            }
            .tt-account {
                display:block;
                font-size:13px;
                font-weight:700;
                color:var(--forest);
            }
            .tt-features,.tt-courts {
                grid-template-columns:repeat(2,minmax(0,1fr));
            }
            .tt-photo {
                height:440px;
            }
            .tt-hero {
                padding-block:60px;
            }
            .tt-footer-grid {
                grid-template-columns:1fr 1fr;
            }
            .tt-final-card {
                padding:45px;
            }
        }
        @media(min-width:1024px) {
            .tt-desktop-links {
                display:flex;
                gap:27px;
            }
            .tt-desktop-links a {
                color:var(--muted);
                font-size:13px;
                font-weight:700;
            }
            .tt-desktop-links a:hover {
                color:var(--clay);
            }
            .tt-menu-button,.tt-mobile-menu {
                display:none!important;
            }
            .tt-brand strong {
                font-size:21px;
            }
            .tt-hero-grid {
                grid-template-columns:1fr 1fr;
                gap:65px;
            }
            .tt-photo {
                height:480px;
            }
            .tt-hero {
                padding:66px 0 65px;
            }
            .tt-trust-grid {
                grid-template-columns:repeat(4,minmax(0,1fr));
                gap:30px;
            }
            .tt-trust-item {
                font-size:13px;
            }
            .tt-section {
                padding-block:80px;
            }
            .tt-features {
                grid-template-columns:repeat(4,minmax(0,1fr));
            }
            .tt-steps {
                grid-template-columns:repeat(3,minmax(0,1fr));
                gap:32px;
            }
            .tt-step {
                flex-direction:column;
            }
            .tt-how-bottom {
                flex-direction:row;
                align-items:center;
                justify-content:space-between;
            }
            .tt-courts {
                grid-template-columns:repeat(3,minmax(0,1fr));
            }
            .tt-final-content {
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:35px;
            }
            .tt-final .tt-btn {
                flex-shrink:0;
                margin-top:0;
            }
            .tt-footer-grid {
                grid-template-columns:1.1fr 1fr 1fr;
                gap:55px;
            }
        }
        /* Recorte de la imagen existente para evitar que su rótulo se superponga al texto. */
        .tt-photo img {
            transform:scale(1.18);
            transform-origin:center top;
        }
        /* Verde más oscuro en botones para asegurar contraste con el texto blanco. */
        .tt-btn-primary {
            background:#15803d;
        }
        .tt-btn-primary:hover {
            background:#166534;
        }
        @media(max-width:380px) {
            .tt-brand {
                gap:7px;
            }
            .tt-logo {
                width:30px;
                height:30px;
            }
            .tt-brand strong {
                font-size:16px;
            }
            .tt-brand small {
                font-size:8px;
                letter-spacing:1px;
            }
            .tt-nav-actions {
                gap:7px;
            }
            .tt-nav .tt-btn {
                padding-inline:12px;
            }
        }
        @media(prefers-reduced-motion:reduce) {
            .tt-home * {
                transition:none!important;
                scroll-behavior:auto!important;
            }
        }
        </style>
</head>
<body class="tt-home">
    <a href="#contenido" class="tt-skip">Saltar al contenido</a>
    {{-- Iconos locales reutilizables. --}}
    <svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" aria-hidden="true" style="position:absolute;overflow:hidden">
        <defs>
            <symbol id="tt-ball" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5 6c6 1 6 11 0 12M19 6c-6 1-6 11 0 12"/></symbol>
            <symbol id="tt-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
            <symbol id="tt-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
            <symbol id="tt-person" viewBox="0 0 24 24"><circle cx="12" cy="7" r="3"/><path d="M5 21v-3a7 7 0 0 1 14 0v3"/></symbol>
            <symbol id="tt-qr" viewBox="0 0 24 24"><path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h2v2h-2zM21 15v3h-3v3h-3M15 21h1M21 21h.01"/></symbol>
            <symbol id="tt-pin" viewBox="0 0 24 24"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/></symbol>
            <symbol id="tt-court" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M3 8h18M3 16h18M12 4v16M7 8v8M17 8v8"/></symbol>
            <symbol id="tt-light" viewBox="0 0 24 24"><path d="M9 18h6m-5 3h4M8 14a6 6 0 1 1 8 0l-1 2H9z"/></symbol>
            <symbol id="tt-phone" viewBox="0 0 24 24"><path d="m7 3 3 4-2 3a14 14 0 0 0 6 6l3-2 4 3-1 4C11 23 1 13 3 4z"/></symbol>
            <symbol id="tt-instagram" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/></symbol>
        </defs>
    </svg>

    {{-- NAVBAR: anclas públicas y reserva según la sesión. --}}
    <header class="tt-nav" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
        <nav class="tt-container" aria-label="Navegación principal">
            <div class="tt-nav-row">
                <a href="#contenido" class="tt-brand" aria-label="Top Tennis, inicio">
                    <svg class="tt-icon tt-logo" aria-hidden="true"><use href="#tt-ball"/></svg>
                    <span><strong>Top Tennis</strong><small>Perú · Club de tenis</small></span>
                </a>
                <div class="tt-desktop-links"><a href="#canchas">Canchas</a><a href="#como-funciona">Cómo funciona</a><a href="#contacto">Contacto</a></div>
                <div class="tt-nav-actions">
                    @guest
                        <a class="tt-account" href="{{ route('login') }}">Iniciar sesión</a>
                        <a class="tt-btn tt-btn-primary" href="{{ route('register') }}">Reservar</a>
                    @endguest
                    @auth
                        <a class="tt-account" href="{{ route('dashboard') }}">Mi Panel</a>
                        <a class="tt-btn tt-btn-primary" href="{{ route('reservas.disponibles') }}">Reservar</a>
                    @endauth
                    <button type="button" class="tt-menu-button" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-expanded="false" aria-controls="tt-mobile-menu" aria-label="Abrir o cerrar menú">
                        <svg class="tt-icon" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
            <div id="tt-mobile-menu" class="tt-mobile-menu" x-show="menuOpen" x-cloak>
                <a href="#canchas" @click="menuOpen = false">Canchas</a>
                <a href="#como-funciona" @click="menuOpen = false">Cómo funciona</a>
                <a href="#contacto" @click="menuOpen = false">Contacto</a>
                @guest <a href="{{ route('login') }}">Iniciar sesión</a> @endguest
                @auth <a href="{{ route('dashboard') }}">Mi Panel</a> @endauth
            </div>
        </nav>
    </header>
    <main id="contenido">
        {{-- HERO. Alternativas: «Vuelve a la arcilla. Vuelve a tu juego.» / «Más intercambios. Más tenis. Más arcilla.» --}}
        <section class="tt-hero" aria-labelledby="tt-hero-title">
            <div class="tt-container tt-hero-grid">
                <div>
                    <p class="tt-eyebrow">Tu punto de encuentro en Lima</p>
                    <h1 id="tt-hero-title">Tu mejor plan<br>empieza <span>en la arcilla.</span></h1>
                    <p class="tt-hero-copy">Haz espacio para tu próximo partido. Regístrate en segundos, elige tu hora y disfruta una atención de primer nivel.</p>
                    <div class="tt-hero-buttons">
                        @guest <a href="{{ route('register') }}" class="tt-btn tt-btn-primary">Reservar mi cancha <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endguest
                        @auth <a href="{{ route('reservas.disponibles') }}" class="tt-btn tt-btn-primary">Reservar mi cancha <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endauth
                        <a href="#canchas" class="tt-btn tt-btn-secondary">Ver canchas</a>
                    </div>
                    @guest <p class="tt-registration">Necesitas una cuenta para reservar. ¿Ya tienes una? <a href="{{ route('login') }}">Inicia sesión</a>.</p> @endguest
                    @auth <p class="tt-registration">Tu reserva y tu ticket quedan disponibles en tu cuenta.</p> @endauth
                    <div class="tt-availability"><span class="tt-dot" aria-hidden="true"></span>Consulta disponibilidad en tiempo real</div>
                </div>
                <div class="tt-hero-visual">
                    <div class="tt-photo">
                        <img src="{{ asset('images/Arcilla.jpeg') }}" alt="Superficie de una cancha de tenis de arcilla" width="720" height="800" fetchpriority="high">
                        <span class="tt-photo-badge"><svg class="tt-icon" aria-hidden="true"><use href="#tt-ball"/></svg>Canchas de arcilla</span>
                        <div class="tt-photo-caption"><small>Polvo de ladrillo · Lima, Perú</small><strong>El sonido de un buen partido.</strong><p>La arcilla, tu raqueta y las ganas de jugar.</p></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FRANJA DE CONFIANZA: sin cifras ni valoraciones inventadas. --}}
        <section class="tt-trust" aria-label="La experiencia Top Tennis">
            <div class="tt-container tt-trust-grid">
                <div class="tt-trust-item"><svg class="tt-icon" aria-hidden="true"><use href="#tt-ball"/></svg><span>Arcilla<br>Polvo de ladrillo</span></div>
                <div class="tt-trust-item"><svg class="tt-icon" aria-hidden="true"><use href="#tt-clock"/></svg><span>Solicita tu reserva online<br>a cualquier hora</span></div>
                <div class="tt-trust-item"><svg class="tt-icon" aria-hidden="true"><use href="#tt-person"/></svg><span>Atención<br>personalizada</span></div>
                <div class="tt-trust-item"><svg class="tt-icon" aria-hidden="true"><use href="#tt-qr"/></svg><span>Tu ticket con QR<br>en tu cuenta</span></div>
            </div>
        </section>

        {{-- POR QUÉ JUGAR EN TOP TENNIS --}}
        <section class="tt-section" aria-labelledby="tt-benefits-title">
            <div class="tt-container">
                <p class="tt-eyebrow">Disfruta cada punto</p>
                <h2 id="tt-benefits-title" class="tt-heading">Por qué jugar en Top Tennis</h2>
                <p class="tt-intro">Menos vueltas para organizar tu partido. Más tiempo para hacer lo que te gusta.</p>
                <div class="tt-features">
                    <article class="tt-feature"><div class="tt-feature-icon"><svg class="tt-icon" aria-hidden="true"><use href="#tt-ball"/></svg></div><h3>Canchas de arcilla</h3><p>Disfruta los intercambios y el juego sobre polvo de ladrillo.</p></article>
                    <article class="tt-feature"><div class="tt-feature-icon"><svg class="tt-icon" aria-hidden="true"><use href="#tt-person"/></svg></div><h3>Atención de primer nivel</h3><p>Coordina tu visita con nuestro equipo y resuelve tus dudas antes de jugar.</p></article>
                    <article class="tt-feature"><div class="tt-feature-icon"><svg class="tt-icon" aria-hidden="true"><use href="#tt-clock"/></svg></div><h3>Reserva online en segundos</h3><p>Consulta horarios y solicita tu reserva en pocos pasos, desde tu celular.</p></article>
                    <article class="tt-feature"><div class="tt-feature-icon"><svg class="tt-icon" aria-hidden="true"><use href="#tt-court"/></svg></div><h3>Instalaciones para tu partido</h3><p>Revisa la modalidad y las características de cada cancha antes de elegir tu hora.</p></article>
                </div>
            </div>
        </section>

        {{-- RESERVA EN 3 PASOS: el pago requiere verificación de recepción. --}}
        <section id="como-funciona" class="tt-section tt-how" aria-labelledby="tt-how-title">
            <div class="tt-container">
                <p class="tt-eyebrow">Así de simple</p><h2 id="tt-how-title" class="tt-heading">Reserva en 3 pasos</h2>
                <div class="tt-steps">
                    <article class="tt-step"><span class="tt-step-number" aria-hidden="true">01</span><div><h3>Regístrate o inicia sesión</h3><p>Crea tu cuenta con tu nombre y correo. Si ya tienes una, entra y continúa.</p></div></article>
                    <article class="tt-step"><span class="tt-step-number" aria-hidden="true">02</span><div><h3>Elige fecha y hora</h3><p>Consulta los horarios disponibles y selecciona la cancha para tu próximo partido.</p></div></article>
                    <article class="tt-step"><span class="tt-step-number" aria-hidden="true">03</span><div><h3>Reserva y verifica tu pago en recepción</h3><p>Tu solicitud queda pendiente. Cuando recepción verifica el pago, tu reserva se confirma.</p></div></article>
                </div>
                <div class="tt-how-bottom">
                    <p class="tt-how-note">Puedes consultar tu ticket mientras tu reserva está pendiente. El QR permite el ingreso cuando el pago está aprobado y se cumplen las condiciones de tu reserva.</p>
                    @guest <a href="{{ route('register') }}" class="tt-btn tt-btn-primary">Reservar mi cancha <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endguest
                    @auth <a href="{{ route('reservas.disponibles') }}" class="tt-btn tt-btn-primary">Reservar mi cancha <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endauth
                </div>
            </div>
        </section>

        {{-- NUESTRAS CANCHAS: conservar los datos reales, incluida su superficie. --}}
        <section id="canchas" class="tt-section" aria-labelledby="tt-courts-title">
            <div class="tt-container">
                <div class="tt-courts-heading">
                    <div><p class="tt-eyebrow">Encuentra tu próximo partido</p><h2 id="tt-courts-title" class="tt-heading">Nuestras canchas</h2><p class="tt-intro">Revisa sus características y elige tu horario en la página de reservas.</p></div>
                    <p class="tt-location"><svg class="tt-icon" aria-hidden="true"><use href="#tt-pin"/></svg>Vía Láctea 301, Lima</p>
                </div>
                <div class="tt-courts">
                    @forelse(($canchas ?? []) as $cancha)
                        <article class="tt-court">
                            <div class="tt-court-image">
                                <img src="{{ $cancha->imagenUrl() }}" alt="{{ $cancha->nombre }} — superficie {{ $cancha->tipo_superficie }}" loading="lazy" width="600" height="400">
                                <span class="tt-surface">{{ $cancha->tipo_superficie }}</span>
                            </div>
                            <div class="tt-court-body">
                                <h3>{{ $cancha->nombre }}</h3>
                                <div class="tt-court-meta">
                                    <span><svg class="tt-icon" aria-hidden="true"><use href="#tt-person"/></svg>{{ $cancha->modalidad ?? 'Consulta la modalidad' }}</span>
                                    <span><svg class="tt-icon" aria-hidden="true"><use href="#tt-court"/></svg>{{ $cancha->tipo_superficie }}</span>
                                    @if($cancha->iluminacion)<span><svg class="tt-icon" aria-hidden="true"><use href="#tt-light"/></svg>Con iluminación</span>@endif
                                </div>
                                @guest <a href="{{ route('register') }}" class="tt-court-link" aria-label="Consultar horarios para reservar, {{ $cancha->nombre }}">Ver horarios y reservar <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endguest
                                @auth <a href="{{ route('reservas.disponibles') }}" class="tt-court-link" aria-label="Consultar horarios para reservar, {{ $cancha->nombre }}">Ver horarios y reservar <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endauth
                            </div>
                        </article>
                    @empty
                        <p class="tt-empty">No hay canchas publicadas en este momento. Contacta al club para consultar cuándo estarán disponibles.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- PRUEBA SOCIAL omitida: no se proporcionaron reseñas verificadas. --}}

        {{-- CTA FINAL --}}
        <section class="tt-final" aria-labelledby="tt-final-title">
            <div class="tt-container"><div class="tt-final-card"><div class="tt-final-content">
                <div><p class="tt-eyebrow">Nos vemos en la cancha</p><h2 id="tt-final-title">Tu próxima hora en arcilla te está esperando</h2><p>Elige tu horario. Organiza tu partido. Disfruta cada punto.</p></div>
                @guest <a href="{{ route('register') }}" class="tt-btn tt-btn-primary">Reservar mi cancha <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endguest
                @auth <a href="{{ route('reservas.disponibles') }}" class="tt-btn tt-btn-primary">Reservar mi cancha <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a> @endauth
            </div></div></div>
        </section>
    </main>

    {{-- FOOTER / CONTACTO: datos proporcionados por el club. --}}
    <footer id="contacto" class="tt-footer">
        <div class="tt-container">
            <div class="tt-footer-grid">
                <div>
                    <a href="#contenido" class="tt-brand" aria-label="Top Tennis, volver al inicio"><svg class="tt-icon tt-logo" aria-hidden="true"><use href="#tt-ball"/></svg><span><strong>Top Tennis</strong><small>Perú · Club de tenis</small></span></a>
                    <p class="tt-footer-intro">Tu punto de encuentro con el tenis y la arcilla en Lima.</p>
                    <a class="tt-contact-link" href="https://www.instagram.com/toptennisperu/" target="_blank" rel="noopener noreferrer" aria-label="Top Tennis Perú en Instagram, abre otra pestaña"><svg class="tt-icon" aria-hidden="true"><use href="#tt-instagram"/></svg>@toptennisperu</a>
                </div>
                <div>
                    <h2>Visítanos y conversemos</h2><address>Vía Láctea 301, Lima 15023</address>
                    <a class="tt-contact-link" href="tel:+51998188817"><svg class="tt-icon" aria-hidden="true"><use href="#tt-phone"/></svg>998 188 817</a>
                    <a class="tt-contact-link" href="https://wa.me/51998188817" target="_blank" rel="noopener noreferrer" aria-label="Escríbenos por WhatsApp, abre otra pestaña">Escríbenos por WhatsApp <svg class="tt-icon" aria-hidden="true"><use href="#tt-arrow"/></svg></a>
                </div>
                <div>
                    <h2>Horario de atención</h2>
                    @php
                        $reglasClub = \App\Models\ClubSetting::reglas();
                        $gruposHorario = [];
                        foreach ([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'] as $dia => $nombreDia) {
                            $horasDia = \App\Models\ClubSetting::horarioDelDia($dia, $reglasClub);
                            $textoHorario = $horasDia ? $horasDia['apertura'].'–'.$horasDia['cierre'] : 'Cerrado';
                            $ultimo = count($gruposHorario) - 1;
                            if ($ultimo >= 0 && $gruposHorario[$ultimo]['horario'] === $textoHorario) {
                                $gruposHorario[$ultimo]['dias'][] = $nombreDia;
                            } else {
                                $gruposHorario[] = ['dias' => [$nombreDia], 'horario' => $textoHorario];
                            }
                        }
                    @endphp
                    @foreach($gruposHorario as $grupoHorario)
                        <p>{{ count($grupoHorario['dias']) > 1 ? $grupoHorario['dias'][0].'–'.end($grupoHorario['dias']) : $grupoHorario['dias'][0] }} {{ $grupoHorario['horario'] }}</p>
                    @endforeach
                </div>
            </div>
            <div class="tt-footer-bottom"><p>© {{ date('Y') }} Top Tennis Perú. Todos los derechos reservados.</p><p>Hecho para disfrutar el tenis.</p></div>
        </div>
    </footer>
</body>
</html>
