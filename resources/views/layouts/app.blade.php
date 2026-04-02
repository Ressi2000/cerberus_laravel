@props(['header' => null, 'title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' · Cerberus' : config('app.name', 'Cerberus') }}</title>

    {{--
        ANTI-FLASH 1 — Tema oscuro/claro
        Corre SÍNCRONAMENTE antes de cualquier pixel renderizado.
        Nunca async/defer ni en archivo externo.
    --}}
    <script>
        (function () {
            var saved = localStorage.getItem('cerberus-theme');
            var sys   = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved !== null ? saved === 'dark' : sys) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{--
        ANTI-FLASH 2 — Mini sidebar
        Escribe el atributo data-sidebar en <html> antes de pintar,
        así el CSS inline de abajo ya tiene el valor correcto.
    --}}
    <script>
        (function () {
            var mini = localStorage.getItem('cerberus_sidebar_mini') === 'true';
            document.documentElement.setAttribute('data-sidebar', mini ? 'mini' : 'full');
        })();
    </script>

    {{--
        CSS CRÍTICO INLINE — anchos del sidebar y margen del contenido.
        Tailwind no puede manejar esto dinámicamente sin purgar las clases.
        Las transiciones se activan SÓLO después de que Alpine inicializa
        (clase .sidebar-ready) para evitar el flash de transición al cargar.
    --}}
    <style>
        /* Sidebar */
        #sidebar { width: 16rem; }
        html[data-sidebar="mini"] #sidebar { width: 60px; }

        /* Margen del contenido en desktop */
        @media (min-width: 1024px) {
            #main-content { margin-left: 16rem; }
            html[data-sidebar="mini"] #main-content { margin-left: 60px; }
        }

        /* Ocultar texto del sidebar en modo mini */
        html[data-sidebar="mini"] .sidebar-label { opacity: 0; width: 0; overflow: hidden; }
        html[data-sidebar="mini"] .sidebar-arrow { opacity: 0; width: 0; overflow: hidden; }
        html[data-sidebar="mini"] .sidebar-submenu { display: none !important; }
        html[data-sidebar="mini"] .sidebar-badge  { display: none !important; }

        /* Logo */
        html[data-sidebar="mini"] .sidebar-logo-text { opacity: 0; width: 0; overflow: hidden; }

        /* Transición SÓLO después de init de Alpine (evita flash al cargar) */
        .sidebar-ready #sidebar,
        .sidebar-ready #main-content {
            transition: width 280ms cubic-bezier(0.4,0,0.2,1),
                        margin-left 280ms cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar-ready .sidebar-label,
        .sidebar-ready .sidebar-logo-text,
        .sidebar-ready .sidebar-arrow {
            transition: opacity 200ms ease, width 280ms ease;
        }
    </style>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-100 dark:bg-cerberus-dark text-[#1E293B] dark:text-gray-100 antialiased"
      x-data="cerberusDarkMode()"
      :class="{ 'dark': isDark }">

    {{-- Sidebar --}}
    <x-ui.sidebar />

    {{-- Contenido principal --}}
    <div id="main-content" class="flex flex-col min-h-screen">

        {{-- Navbar — recibe $header desde x-app-layout --}}
        <x-ui.navbar :header="$header ?? 'Dashboard'" />

        {{-- Página --}}
        <main class="flex-1 p-4 sm:p-6">
            {{ $slot }}
        </main>

        <x-ui.footer />
    </div>

    <script>
        // Activar transiciones sólo después de que Alpine esté listo
        document.addEventListener('alpine:initialized', function () {
            document.body.classList.add('sidebar-ready');
        });

        // Reaplicar tema en navegaciones SPA (wire:navigate)
        document.addEventListener('livewire:navigated', function () {
            var saved = localStorage.getItem('cerberus-theme');
            var sys   = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var dark  = saved !== null ? saved === 'dark' : sys;
            document.documentElement.classList.toggle('dark', dark);
        });

        // ── Toggle mini-sidebar ──────────────────────────────────────────────
        // Escucha el evento personalizado que despacha el botón del navbar
        window.addEventListener('cerberus:sidebar-toggle', function () {
            var cur  = document.documentElement.getAttribute('data-sidebar');
            var next = cur === 'mini' ? 'full' : 'mini';
            document.documentElement.setAttribute('data-sidebar', next);
            localStorage.setItem('cerberus_sidebar_mini', next === 'mini' ? 'true' : 'false');

            // Actualizar ícono en el navbar
            var icon = document.getElementById('sidebar-toggle-icon');
            if (icon) icon.textContent = next === 'mini' ? 'menu' : 'menu_open';
        });

        // ── Sidebar móvil ────────────────────────────────────────────────────
        // Delegación de eventos — compatible con wire:navigate (sin re-bind)
        document.addEventListener('click', function (e) {
            var sidebar  = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');

            function open() {
                if (!sidebar) return;
                sidebar.classList.remove('-translate-x-full');
                if (backdrop) backdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            function close() {
                if (!sidebar) return;
                sidebar.classList.add('-translate-x-full');
                if (backdrop) backdrop.classList.add('hidden');
                document.body.style.overflow = '';
            }

            if (e.target.closest('#mobile-sidebar-toggle')) open();
            if (e.target.closest('#sidebar-close'))         close();
            if (backdrop && (e.target === backdrop || e.target.closest('#sidebar-backdrop') === backdrop)) close();
        });

        // Cerrar sidebar móvil al cambiar a desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024) {
                var sidebar  = document.getElementById('sidebar');
                var backdrop = document.getElementById('sidebar-backdrop');
                if (sidebar)  sidebar.classList.remove('-translate-x-full');
                if (backdrop) backdrop.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
    </script>

    @livewireScripts
</body>
</html>