@props(['header' => null, 'title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">
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

<body class="bg-cerberus-dark text-[#1E293B] dark:text-gray-100 antialiased"
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

    {{-- ── Toast global de notificaciones ────────────────────────────────── --}}
    {{-- Recibe eventos 'toast' desde cualquier componente Livewire del sistema --}}
    <livewire:toast-notification />

    {{-- ── Toast client-side para errores HTTP/red de Livewire (404/419/500) ── --}}
    {{-- Funciona sin round-trip al servidor para evitar bucles si la petición falla --}}
    <div x-data="{
            items: [],
            nextId: 0,
            add(detail) {
                const msg = detail?.message || 'Ocurrió un error inesperado.';
                // Evita apilar toasts idénticos (p. ej. durante un wire:poll)
                if (this.items.some(i => i.message === msg)) return;
                const id = ++this.nextId;
                this.items.push({
                    id,
                    type: detail?.type || 'error',
                    title: detail?.title || 'Error de conexión',
                    message: msg,
                    reload: Boolean(detail?.reload),
                });
                setTimeout(() => this.remove(id), detail?.duration || 7000);
            },
            remove(id) {
                this.items = this.items.filter(i => i.id !== id);
            }
         }"
         x-on:cerberus-client-toast.window="add($event.detail)"
         class="fixed bottom-5 left-5 z-[9999] flex flex-col gap-3 w-80 max-w-[calc(100vw-2.5rem)] pointer-events-none print:hidden"
         aria-live="assertive">
        <template x-for="item in items" :key="item.id">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                 class="pointer-events-auto w-full rounded-xl overflow-hidden shadow-lg border bg-white dark:bg-cerberus-mid"
                 :class="item.type === 'warning' ? 'border-amber-400/60' : 'border-red-400/60'"
                 role="alert">
                <div class="flex items-start gap-3 px-4 py-3">
                    <span class="material-icons text-xl flex-shrink-0 mt-0.5"
                          :class="item.type === 'warning' ? 'text-amber-500' : 'text-red-500'"
                          x-text="item.type === 'warning' ? 'warning' : 'error'"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white leading-snug" x-text="item.title"></p>
                        <p class="text-xs text-gray-600 dark:text-cerberus-light mt-0.5 break-words" x-text="item.message"></p>
                        <template x-if="item.reload">
                            <button type="button" x-on:click="window.location.reload()"
                                class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-[#1E40AF] dark:text-cerberus-accent hover:underline">
                                <span class="material-icons text-sm">refresh</span>
                                Recargar página
                            </button>
                        </template>
                    </div>
                    <button type="button" x-on:click="remove(item.id)"
                        class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-white" aria-label="Cerrar">
                        <span class="material-icons text-base">close</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- ── Modal de confirmación global ──────────────────────────────────── --}}
    {{-- Recibe el evento 'confirmar' desde cualquier componente Livewire del
         sistema y reemplaza window.confirm() nativo (ver App\Livewire\Ui\ConfirmModal). --}}
    <livewire:ui.confirm-modal />

    {{-- ── Cerberus Bot (prueba: solo donde ASISTENTE_HABILITADO=true) ──── --}}
    @if (\App\Asistente\Asistente::disponiblePara(auth()->user()) && ! request()->routeIs('asistente.index'))
        @persist('cerberus-bot')
            <livewire:asistente.chat-asistente />
        @endpersist
    @endif

    <script>
        // Proteger contra registros duplicados al navegar con wire:navigate:
        // Livewire re-ejecuta los <script> del <body> en cada navegación SPA;
        // si se duplican los listeners, el toggle del sidebar se dispara dos veces y se cancela.
        if (!window.__cerberusLayoutInit) {
            window.__cerberusLayoutInit = true;

            function syncSidebarUi() {
                var mini = localStorage.getItem('cerberus_sidebar_mini') === 'true';
                document.documentElement.setAttribute('data-sidebar', mini ? 'mini' : 'full');
                var icon = document.getElementById('sidebar-toggle-icon');
                if (icon) icon.textContent = mini ? 'menu' : 'menu_open';
                document.body.classList.add('sidebar-ready');
            }

            // Activar transiciones sólo después de que Alpine esté listo
            document.addEventListener('alpine:initialized', function () {
                document.body.classList.add('sidebar-ready');
            });

            // Reaplicar tema y estado del sidebar en navegaciones SPA (wire:navigate)
            document.addEventListener('livewire:navigated', function () {
                var saved = localStorage.getItem('cerberus-theme');
                var sys   = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var dark  = saved !== null ? saved === 'dark' : sys;
                document.documentElement.classList.toggle('dark', dark);
                syncSidebarUi();
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

            // ── Manejo global de errores HTTP en peticiones Livewire ────────────
            // Evita el modal iframe invasivo de Livewire ante 404/419/500 y muestra
            // un aviso limpio y recuperable al usuario.
            var recargandoPorSesion = false;
            document.addEventListener('livewire:init', function () {
                if (window.__cerberusLivewireHook || typeof Livewire === 'undefined') return;
                window.__cerberusLivewireHook = true;

                Livewire.hook('request', function ({ fail }) {
                    fail(function ({ status, preventDefault }) {
                        // Siempre impedir el modal HTML/iframe crudo de Livewire
                        preventDefault();

                        if (status === 419) {
                            if (recargandoPorSesion) return;
                            recargandoPorSesion = true;
                            window.dispatchEvent(new CustomEvent('cerberus-client-toast', {
                                detail: {
                                    type: 'warning',
                                    title: 'Sesión expirada',
                                    message: 'Tu sesión caducó. Recargando la página…',
                                    duration: 4000,
                                }
                            }));
                            setTimeout(function () { window.location.reload(); }, 1500);
                            return;
                        }

                        console.error('[Cerberus Livewire] Error HTTP en petición:', status);

                        if (status === 403) {
                            window.dispatchEvent(new CustomEvent('cerberus-client-toast', {
                                detail: {
                                    type: 'warning',
                                    title: 'Acceso denegado',
                                    message: 'No tienes permisos para realizar esta acción.',
                                }
                            }));
                            return;
                        }

                        if (status === 404) {
                            window.dispatchEvent(new CustomEvent('cerberus-client-toast', {
                                detail: {
                                    type: 'error',
                                    title: 'Recurso no encontrado (404)',
                                    message: 'El registro o la acción solicitada ya no está disponible.',
                                    reload: true,
                                }
                            }));
                            return;
                        }

                        if (status === 0) {
                            window.dispatchEvent(new CustomEvent('cerberus-client-toast', {
                                detail: {
                                    type: 'warning',
                                    title: 'Sin conexión',
                                    message: 'No se pudo conectar con el servidor. Verifica tu red e intenta de nuevo.',
                                }
                            }));
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('cerberus-client-toast', {
                            detail: {
                                type: 'error',
                                title: 'Error del servidor (' + status + ')',
                                message: 'No se pudo completar la acción. Intenta de nuevo o recarga la página.',
                                reload: true,
                            }
                        }));
                    });
                });
            });
        }
    </script>

    @livewireScripts
</body>
</html>