<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="cerberusDarkMode()" :class="{ 'dark': isDark }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cerberus 2.0 – Sistema de Inventario y Asignaciones Tecnológicas</title>
    <link rel="icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">
    <meta name="description" content="Cerberus 2.0 centraliza el control de activos tecnológicos en un entorno multiempresa: asignaciones, préstamos, traslados y auditoría completa, con acceso diferenciado por roles.">
    <meta property="og:title" content="Cerberus 2.0 – Sistema de Inventario y Asignaciones Tecnológicas">
    <meta property="og:description" content="Gestión inteligente de tu inventario tecnológico: asignaciones, préstamos, traslados y auditoría completa.">
    <meta property="og:type" content="website">

    {{-- Anti-flash: misma clave de localStorage que el resto del sistema --}}
    <script>
        (function () {
            const saved = localStorage.getItem('cerberus-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (saved === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="min-h-screen flex flex-col font-sans antialiased transition-colors duration-500
             bg-[#E2E8F0] dark:bg-[#0D1B2A]
             text-[#1E293B] dark:text-[#F1F5F9]">

    <!-- ══════════════════════════════════════════════════════════
         HERO — fondo siempre oscuro (video): colores fijos
         ══════════════════════════════════════════════════════════ -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden"
        x-data="{
            muted: true,
            mobileNavOpen: false,
            toggleMute() {
                this.muted = !this.muted;
                this.$refs.video.muted = this.muted;
            }
        }">

        <!-- Video de fondo -->
        <div class="absolute inset-0 z-0">
            <div class="w-full h-full overflow-hidden">
                <video x-ref="video"
                    autoplay loop playsinline muted preload="metadata"
                    poster="{{ asset('images/CB2.0.gif') }}"
                    class="w-full h-full object-cover object-center">
                    <source src="{{ asset('images/CB2.0.mp4') }}" type="video/mp4">
                    <img src="{{ asset('images/CB2.0.gif') }}" alt="Cerberus Background"
                        class="w-full h-full object-cover object-center"
                        style="opacity: 0.9;">
                </video>
            </div>
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80"></div>
        </div>

        <!-- Botón mute/unmute -->
        <button type="button" @click="toggleMute()"
            class="absolute bottom-6 right-6 z-20 p-3 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-sm text-white transition-all duration-300"
            :aria-label="muted ? 'Activar sonido' : 'Silenciar sonido'"
            :title="muted ? 'Activar sonido' : 'Silenciar sonido'">
            <!-- Ícono: silenciado -->
            <svg x-show="muted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M13 3.586L7.707 8.879A1 1 0 017 9H4a1 1 0 00-1 1v4a1 1 0 001 1h3a1 1 0 01.707.293L13 20.414V3.586z"/>
                <line x1="17" y1="9" x2="23" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="23" y1="9" x2="17" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <!-- Ícono: con sonido -->
            <svg x-show="!muted" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor" stroke="none"/>
                <path d="M19.07 4.93a10 10 0 010 14.14"/>
                <path d="M15.54 8.46a5 5 0 010 7.07"/>
            </svg>
        </button>

        <!-- NAVBAR -->
        <header class="absolute top-0 left-0 right-0 z-20 w-full px-6 sm:px-8 py-6 flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center gap-3">
                {{-- Logo fijo (variante "dark"): el Hero siempre tiene fondo oscuro --}}
                <img src="{{ asset('images/logos/crb2-dark.png') }}" alt="Cerberus Logo"
                    class="h-12 w-auto">
                <h1 class="text-2xl font-semibold tracking-tight text-white">
                    Cerberus <span class="text-[#A9D6E5]">2.0</span>
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <!-- Toggle modo oscuro/claro -->
                <button type="button" @click="toggle()"
                    class="p-2.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-sm text-white transition-all duration-300"
                    :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                    aria-label="Cambiar tema">
                    <span class="material-icons text-base" x-show="isDark" style="display:none">light_mode</span>
                    <span class="material-icons text-base" x-show="!isDark">dark_mode</span>
                </button>

                @if (Route::has('login'))
                    <!-- Nav desktop -->
                    <nav class="hidden sm:flex items-center gap-4 text-sm">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                                class="px-5 py-2 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white rounded-md shadow transition">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="px-5 py-2 border border-white/30 hover:border-white/60
                                      text-white hover:bg-white/10 rounded-md transition font-medium backdrop-blur-sm">
                                Iniciar sesión
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                    class="px-5 py-2 bg-[#A9D6E5] hover:bg-[#89C2D9]
                                          text-[#0D1B2A] rounded-md shadow transition font-medium">
                                    Registrarse
                                </a>
                            @endif
                        @endauth
                    </nav>

                    <!-- Botón hamburguesa mobile -->
                    <button type="button" @click="mobileNavOpen = !mobileNavOpen"
                        class="sm:hidden p-2 rounded-md border border-white/30 text-white"
                        aria-label="Abrir menú de navegación">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileNavOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileNavOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

            @if (Route::has('login'))
                <!-- Nav mobile desplegable -->
                <nav x-show="mobileNavOpen" x-cloak x-transition
                    class="sm:hidden absolute top-full left-0 right-0 mt-2 mx-6 p-4 rounded-xl bg-[#0D1B2A]/95 border border-white/10 backdrop-blur-sm flex flex-col gap-3 text-sm">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="px-5 py-2 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white rounded-md shadow transition text-center">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-5 py-2 border border-white/30 hover:border-white/60 text-white hover:bg-white/10 rounded-md transition font-medium text-center">
                            Iniciar sesión
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-5 py-2 bg-[#A9D6E5] hover:bg-[#89C2D9] text-[#0D1B2A] rounded-md shadow transition font-medium text-center">
                                Registrarse
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <!-- CONTENIDO CENTRAL -->
        <div class="relative z-10 text-center max-w-5xl mx-auto px-6">
            <div class="space-y-8">
                <div class="inline-block px-4 py-2 rounded-full border border-white/20 backdrop-blur-sm bg-white/5">
                    <span class="text-white/80 text-sm font-medium">🚀 Versión 2.0</span>
                </div>

                <h1 class="text-5xl md:text-7xl font-extrabold leading-tight text-white">
                    Gestión inteligente de tu
                    <span class="text-[#A9D6E5] block mt-2">inventario tecnológico</span>
                </h1>

                <p class="text-xl text-white/80 max-w-3xl mx-auto leading-relaxed">
                    Cerberus 2.0 centraliza el control de activos tecnológicos en un entorno multiempresa:
                    asignaciones, préstamos, traslados y auditoría completa, con acceso diferenciado por roles.
                </p>

                <div class="flex flex-wrap justify-center gap-3">
                    @foreach (['Multiempresa', 'Control por roles', 'Atributos dinámicos', 'Trazabilidad total', 'Exportación Excel & PDF'] as $badge)
                        <span class="px-4 py-2 text-sm font-medium rounded-full
                                   bg-white/10 backdrop-blur-sm text-white border border-white/20">
                            {{ $badge }}
                        </span>
                    @endforeach
                </div>

                <div class="flex flex-wrap justify-center gap-4 pt-4">
                    <a href="{{ route('login') }}"
                        class="px-8 py-4 bg-[#A9D6E5] hover:bg-[#89C2D9]
                              text-[#0D1B2A] rounded-lg font-semibold text-lg transition shadow-lg
                              hover:scale-105 transform duration-300 motion-reduce:transform-none">
                        Iniciar sesión
                    </a>
                    <a href="#anatomia"
                        class="px-8 py-4 border-2 border-white/30 hover:border-white/60
                              text-white hover:bg-white/10 rounded-lg font-semibold text-lg transition
                              backdrop-blur-sm hover:scale-105 transform duration-300 motion-reduce:transform-none">
                        Conocer más ↓
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         ANATOMÍA DEL SISTEMA — acróstico CERBERUS, en secciones fijas
         (no carrusel): gradiente siempre oscuro, colores fijos
         ══════════════════════════════════════════════════════════ -->
    <section id="anatomia" class="py-24 w-full relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#0D1B2A] via-[#122744] to-[#0A1628]"></div>
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-10 left-1/4 w-96 h-96 bg-[#1E40AF]/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-1/4 w-72 h-72 bg-[#A9D6E5]/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#1E40AF]/10 border border-[#A9D6E5]/20 text-[#A9D6E5] text-sm font-medium mb-4 backdrop-blur-sm">
                    🐺 Anatomía del sistema
                </span>
                <h3 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                    ¿Qué significa <span class="text-[#A9D6E5]">CERBERUS</span>?
                </h3>
                <p class="text-gray-300 max-w-2xl mx-auto text-lg">
                    Ocho pilares que definen la arquitectura y la identidad de la plataforma
                </p>
            </div>

            <!-- Resumen del acróstico -->
            <div class="grid grid-cols-4 sm:grid-cols-8 gap-3 max-w-4xl mx-auto mb-16">
                @foreach ([
                    ['C', 'Control'],
                    ['E', 'Eficiencia'],
                    ['R', 'Roles'],
                    ['B', 'Business'],
                    ['E', 'Evolución'],
                    ['R', 'Rastreo'],
                    ['U', 'Unificación'],
                    ['S', 'Seguridad'],
                ] as [$letra, $palabra])
                    <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center
                                hover:border-[#A9D6E5]/50 hover:bg-white/10 hover:-translate-y-1
                                transition-all duration-300 motion-reduce:hover:translate-y-0">
                        <span class="block text-lg font-extrabold text-[#A9D6E5] mb-1">{{ $letra }}</span>
                        <span class="block text-[10px] uppercase tracking-wide text-gray-400 font-medium">{{ $palabra }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Los ocho pilares, en tarjetas fijas (no diapositivas) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                @foreach ([
                    ['C', '🎛️', 'Control', 'Gestión centralizada y absoluta de todos los activos tecnológicos en un entorno unificado y seguro.', 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=900&q=70'],
                    ['E', '⚡', 'Eficiencia', 'Optimización de procesos operativos diarios como asignaciones, préstamos y traslados de equipos.', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=70'],
                    ['R', '🛡️', 'Roles', 'Seguridad avanzada y acceso diferenciado según los permisos y perfiles específicos de los usuarios.', 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=900&q=70'],
                    ['B', '🏢', 'Business (Multiempresa)', 'Capacidad corporativa de administrar múltiples organizaciones o divisiones desde una sola plataforma.', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=900&q=70'],
                    ['E', '🔧', 'Evolución', 'Adaptabilidad y flexibilidad total gracias a atributos dinámicos que se ajustan a cada necesidad.', 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=70'],
                    ['R', '📡', 'Rastreo (Trazabilidad Total)', 'Seguimiento detallado del ciclo de vida completo de cada activo tecnológico registrado.', 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=900&q=70'],
                    ['U', '📊', 'Unificación', 'Integración de reportes y auditorías con capacidad de exportación directa a Excel y PDF.', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=900&q=70'],
                    ['S', '🔒', 'Seguridad', 'Transparencia, protección de datos y cumplimiento normativo riguroso en cada movimiento.', 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=900&q=70'],
                ] as [$letra, $icono, $titulo, $desc, $imagen])
                    <div class="group relative overflow-hidden rounded-2xl border border-white/10
                                hover:border-[#A9D6E5]/40 transition-all duration-500
                                min-h-[280px] flex flex-col justify-end p-6
                                hover:-translate-y-2 motion-reduce:hover:-translate-y-0">
                        <div class="absolute inset-0 bg-cover bg-center scale-105 group-hover:scale-110
                                    transition-transform duration-700 motion-reduce:transform-none"
                             style="background-image:url('{{ $imagen }}')"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-[#060913] via-[#060913]/85 to-[#060913]/40"></div>

                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 rounded-xl bg-[#1E40AF]/20 border border-[#A9D6E5]/30
                                            flex items-center justify-center text-xl">
                                    {{ $icono }}
                                </div>
                                <span class="text-4xl font-black bg-gradient-to-br from-white to-[#A9D6E5]
                                             bg-clip-text text-transparent opacity-90">{{ $letra }}</span>
                            </div>
                            <h4 class="text-lg font-bold text-white mb-2">{{ $titulo }}</h4>
                            <p class="text-sm text-gray-300 leading-relaxed">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         CIERRE — "Lleva el control total de tu tecnología"
         (panel de la propuesta), siempre oscuro: colores fijos
         ══════════════════════════════════════════════════════════ -->
    <section class="py-24 w-full text-center relative overflow-hidden bg-[#060913]">
        <div class="relative z-10 max-w-4xl mx-auto px-6">
            <div class="relative overflow-hidden rounded-3xl p-12 sm:p-16 border border-white/5"
                 style="background: radial-gradient(circle at center, rgba(59, 130, 246, 0.12) 0%, transparent 70%);">
                <h3 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4 text-white">
                    Lleva el control total de tu tecnología
                </h3>
                <p class="text-gray-400 mb-10 max-w-xl mx-auto text-lg">
                    Optimiza los recursos, automatiza la trazabilidad y asegura el rendimiento de tu
                    infraestructura con Cerberus 2.0.
                </p>
                <a href="{{ route('login') }}"
                    class="group inline-flex items-center gap-3 px-9 py-4
                          bg-gradient-to-r from-[#3b82f6] to-[#2563eb] hover:from-[#2563eb] hover:to-[#1d4ed8]
                          text-white rounded-full font-semibold text-lg
                          shadow-[0_10px_25px_rgba(59,130,246,0.4)] hover:shadow-[0_15px_30px_rgba(59,130,246,0.6)]
                          transition-all duration-300 hover:-translate-y-0.5 motion-reduce:transform-none">
                    Comenzar Ahora
                    <span class="group-hover:translate-x-1 transition-transform duration-300 motion-reduce:transform-none">→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         FOOTER — gradiente siempre oscuro: colores fijos
         ══════════════════════════════════════════════════════════ -->
    <footer class="relative w-full overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#0A1628] via-[#1a3a5c] to-[#0D1B2A]"></div>
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#A9D6E5]/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#1E40AF]/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 py-12 text-gray-300 text-sm">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-3">
                        {{-- Logo fijo (variante "dark"): el footer siempre tiene fondo oscuro --}}
                        <img src="{{ asset('images/logos/crb2-dark.png') }}" alt="Cerberus Logo" loading="lazy"
                            class="h-8 w-auto opacity-70">
                        <span class="text-white/80 font-semibold">Cerberus 2.0</span>
                    </div>

                    <div class="flex gap-8 text-sm">
                        <a href="#" class="hover:text-[#A9D6E5] transition-colors duration-300">Política de privacidad</a>
                        <a href="#" class="hover:text-[#A9D6E5] transition-colors duration-300">Términos de uso</a>
                        <a href="#" class="hover:text-[#A9D6E5] transition-colors duration-300">Contacto</a>
                    </div>

                    <div class="text-gray-400">
                        © {{ date('Y') }} R - A - H
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        html { scroll-behavior: smooth; }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</body>

</html>
