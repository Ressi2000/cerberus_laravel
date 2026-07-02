<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cerberus 2.0 – Sistema de Inventario y Asignaciones Tecnológicas</title>
    <meta name="description" content="Cerberus 2.0 centraliza el control de activos tecnológicos en un entorno multiempresa: asignaciones, préstamos, traslados y auditoría completa, con acceso diferenciado por roles.">
    <meta property="og:title" content="Cerberus 2.0 – Sistema de Inventario y Asignaciones Tecnológicas">
    <meta property="og:description" content="Gestión inteligente de tu inventario tecnológico: asignaciones, préstamos, traslados y auditoría completa.">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
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
                <img src="{{ asset('images/cerberusLight.png') }}" alt="Cerberus Logo"
                    class="h-12 w-auto brightness-0 invert">
                <h1 class="text-2xl font-semibold tracking-tight text-white">
                    Cerberus <span class="text-[#A9D6E5]">2.0</span>
                </h1>
            </div>

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
                    <a href="#features"
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
         FEATURES — gradiente siempre oscuro: colores fijos
         ══════════════════════════════════════════════════════════ -->
    <section id="features" class="py-24 w-full relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#0A1628] via-[#1a3a5c] to-[#0D1B2A]"></div>
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-20 right-20 w-96 h-96 bg-[#A9D6E5]/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 left-20 w-72 h-72 bg-[#1E40AF]/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto text-center px-6">
            <div class="mb-16">
                <span class="inline-block px-4 py-2 rounded-full bg-[#A9D6E5]/10 border border-[#A9D6E5]/20 text-[#A9D6E5] text-sm font-medium mb-4 backdrop-blur-sm">
                    🚀 Características
                </span>
                <h3 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                    ¿Qué puede hacer <span class="text-[#A9D6E5]">Cerberus</span>?
                </h3>
                <p class="text-gray-300 max-w-2xl mx-auto text-lg">Módulos disponibles en la plataforma</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ([
                    ['🖥️', 'Inventario de Equipos', 'Registra y clasifica activos tecnológicos con categorías configurables, estados, ubicaciones y atributos dinámicos por tipo de equipo (RAM, disco, S/N, etc.).'],
                    ['📋', 'Asignaciones', 'Asigna equipos a usuarios o áreas comunes de forma permanente. Soporta periféricos vinculados al equipo principal con devoluciones independientes.'],
                    ['🔄', 'Préstamos', 'Gestiona préstamos temporales con fechas de vencimiento, alertas de expiración, renovaciones y seguimiento de devoluciones.'],
                    ['🚚', 'Traslados', 'Registra movimientos físicos de equipos entre ubicaciones con numeración automática (TRA-YYYY-NNN) y documentación del proceso.'],
                    ['🔍', 'Auditoría', 'Cada acción queda registrada: quién la realizó, cuándo, y qué cambió. Trazabilidad completa para cumplimiento y control.'],
                    ['⚙️', 'Configuración', 'Administra categorías, estados, ubicaciones, departamentos, cargos y empresas desde un panel centralizado.'],
                ] as [$icon, $title, $desc])
                    <div class="group p-8 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10
                                hover:border-[#A9D6E5]/40 hover:bg-white/10 transition-all duration-500 text-left
                                hover:-translate-y-2 motion-reduce:hover:-translate-y-0
                                hover:shadow-2xl hover:shadow-[#A9D6E5]/5">
                        <div class="text-5xl mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500 motion-reduce:transform-none">
                            {{ $icon }}</div>
                        <h4 class="text-xl font-semibold mb-3 text-[#A9D6E5] group-hover:text-white transition-colors duration-300">
                            {{ $title }}</h4>
                        <p class="text-gray-400 text-sm leading-relaxed group-hover:text-gray-300 transition-colors duration-300">
                            {{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         ROLES — fondo que cambia con el tema: tokens cerberus-*
         ══════════════════════════════════════════════════════════ -->
    <section class="py-24 w-full relative bg-[#F0F4F8] dark:bg-[#0D1B2A] transition-colors duration-500">
        <div class="relative z-10 max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-2 rounded-full
                             bg-cerberus-primary/10 dark:bg-[#A9D6E5]/10
                             border border-cerberus-primary/20 dark:border-[#A9D6E5]/20
                             text-cerberus-primary dark:text-[#A9D6E5]
                             text-sm font-medium mb-4 backdrop-blur-sm">
                    🔐 Control de acceso
                </span>
                <h3 class="text-4xl md:text-5xl font-bold text-[#0D1B2A] dark:text-white transition-colors duration-500">
                    Acceso diferenciado por <span class="text-cerberus-primary dark:text-[#A9D6E5]">roles</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ([
                    ['Administrador', 'Control total del sistema: configuración, usuarios, empresas y todos los datos del inventario.',    'border-[#1E40AF]/20 dark:border-red-500/30   bg-[#1E40AF]/5 dark:bg-red-500/5',   '👑', 'text-[#1E40AF] dark:text-red-400'],
                    ['Analista',      'Gestión operativa de equipos, asignaciones, préstamos y traslados en su contexto de empresa.',       'border-[#1E40AF]/20 dark:border-blue-500/30  bg-[#1E40AF]/5 dark:bg-blue-500/5',  '📊', 'text-[#1E40AF] dark:text-blue-400'],
                    ['Usuario',       'Visualización del inventario asignado y préstamos activos asociados a su perfil.',                   'border-[#1E40AF]/20 dark:border-green-500/30 bg-[#1E40AF]/5 dark:bg-green-500/5', '👤', 'text-[#1E40AF] dark:text-green-400'],
                ] as [$rol, $desc, $style, $emoji, $color])
                    <div class="group p-8 rounded-2xl border {{ $style }} bg-white/80 dark:bg-transparent
                                backdrop-blur-sm hover:-translate-y-3 motion-reduce:hover:-translate-y-0
                                transition-all duration-500 hover:shadow-xl">
                        <div class="text-5xl mb-4 group-hover:scale-110 transition-transform duration-500 motion-reduce:transform-none">
                            {{ $emoji }}</div>
                        <h4 class="font-bold text-2xl mb-3 {{ $color }} group-hover:text-[#0D1B2A] dark:group-hover:text-white transition-colors duration-300">
                            {{ $rol }}</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed group-hover:text-gray-800 dark:group-hover:text-white transition-colors duration-300">
                            {{ $desc }}</p>
                        <div class="mt-6 h-0.5 w-12 bg-[#1E40AF]/20 dark:bg-white/20
                                    group-hover:w-full group-hover:bg-[#1E40AF] dark:group-hover:bg-[#A9D6E5]/50
                                    transition-all duration-500 rounded-full">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         CTA FINAL — fondo que cambia con el tema
         ══════════════════════════════════════════════════════════ -->
    <section class="py-24 w-full text-center relative bg-[#E8EEF2] dark:bg-[#162a3f] transition-colors duration-500">
        <div class="relative z-10 max-w-7xl mx-auto px-6">
            <div class="relative overflow-hidden rounded-3xl p-12
                        bg-gradient-to-r from-[#1E40AF]/20 to-[#1E40AF]/5
                        dark:from-[#1E40AF]/30 dark:to-[#A9D6E5]/30
                        border border-[#1E40AF]/20 dark:border-[#A9D6E5]/20 backdrop-blur-sm">
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-[#1E40AF]/10 dark:bg-[#A9D6E5]/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-[#1E40AF]/5 dark:bg-[#1E40AF]/10 rounded-full blur-3xl"></div>

                <div class="relative z-10">
                    <span class="inline-block px-4 py-2 rounded-full
                                 bg-[#1E40AF]/10 dark:bg-[#A9D6E5]/10
                                 border border-[#1E40AF]/20 dark:border-[#A9D6E5]/20
                                 text-[#1E40AF] dark:text-[#A9D6E5]
                                 text-sm font-medium mb-6 backdrop-blur-sm">
                        🎯 Comienza ahora
                    </span>
                    <h3 class="text-4xl md:text-5xl font-bold mb-6 text-[#0D1B2A] dark:text-white transition-colors duration-500">
                        Controla tu infraestructura con <span class="text-[#1E40AF] dark:text-[#A9D6E5]">Cerberus</span>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto text-lg transition-colors duration-500">
                        Solución moderna, segura y multiempresa para la gestión completa del ciclo de vida de tus
                        activos tecnológicos.
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('login') }}"
                            class="group inline-flex items-center gap-2 px-10 py-5
                                  bg-[#1E40AF] hover:bg-[#1E3A8A] dark:bg-[#A9D6E5] dark:hover:bg-[#89C2D9]
                                  text-white dark:text-[#0D1B2A] rounded-lg font-semibold text-xl
                                  transition-all duration-300 shadow-lg
                                  hover:scale-105 motion-reduce:transform-none
                                  hover:shadow-2xl hover:shadow-[#1E40AF]/20 dark:hover:shadow-[#A9D6E5]/20">
                            Comenzar ahora
                            <span class="group-hover:translate-x-1 transition-transform duration-300 motion-reduce:transform-none">→</span>
                        </a>
                        <a href="#features"
                            class="px-10 py-5 border-2 border-[#1E40AF]/30 dark:border-white/30
                                  hover:border-[#1E40AF]/50 dark:hover:border-[#A9D6E5]/50
                                  text-[#1E40AF] dark:text-white hover:text-[#0D1B2A] dark:hover:text-[#A9D6E5]
                                  rounded-lg font-semibold text-xl transition-all duration-300
                                  backdrop-blur-sm hover:scale-105 motion-reduce:transform-none
                                  hover:shadow-xl hover:bg-white/50 dark:hover:bg-white/5">
                            Explorar características
                        </a>
                    </div>
                </div>
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
                        <img src="{{ asset('images/cerberusLight.png') }}" alt="Cerberus Logo" loading="lazy"
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
