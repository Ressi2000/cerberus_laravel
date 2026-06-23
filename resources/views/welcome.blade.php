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
             bg-cerberus-dark
             text-[#1E293B] dark:text-[#F1F5F9]">

    <!-- HERO PRINCIPAL - FONDO CON EL VIDEO -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden" x-data="{ muted: true, mobileNavOpen: false }">

        <!-- Fondo con el video (ocupando toda la pantalla) -->
        <div class="absolute inset-0 z-0">
            <div class="w-full h-full overflow-hidden">
                <video autoplay loop playsinline muted preload="metadata"
                    poster="{{ asset('images/CB2.0.gif') }}"
                    :muted="muted"
                    class="w-full h-full object-cover object-center">
                    <source src="{{ asset('images/CB2.0.mp4') }}" type="video/mp4">
                    <!-- Fallback si no carga -->
                    <img src="{{ asset('images/CB2.0.gif') }}" alt="Cerberus Background"
                        class="w-full h-full object-cover object-center"
                        style="mix-blend-mode: multiply; opacity: 0.9;">
                </video>
            </div>
            <!-- Overlay oscuro para legibilidad -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80"></div>
        </div>

        <!-- Botón de mute/unmute del video -->
        <button type="button" @click="muted = !muted"
            class="absolute bottom-6 right-6 z-20 p-3 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-sm text-white transition"
            :aria-label="muted ? 'Activar sonido' : 'Silenciar sonido'">
            <svg x-show="muted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.79L4.586 13.5H2a1 1 0 01-1-1v-5a1 1 0 011-1h2.586l3.797-3.29a1 1 0 011-.134zM14.293 7.293a1 1 0 011.414 0L17 8.586l1.293-1.293a1 1 0 111.414 1.414L18.414 10l1.293 1.293a1 1 0 01-1.414 1.414L17 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L15.586 10l-1.293-1.293a1 1 0 010-1.414z" />
            </svg>
            <svg x-show="!muted" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.79L4.586 13.5H2a1 1 0 01-1-1v-5a1 1 0 011-1h2.586l3.797-3.29a1 1 0 011-.134zM14.657 6.343a1 1 0 011.414 0A6 6 0 0118 10a6 6 0 01-1.929 4.243 1 1 0 11-1.414-1.415A4 4 0 0016 10a4 4 0 00-1.343-2.929 1 1 0 010-1.414z" />
            </svg>
        </button>

        <!-- NAVBAR (sobre el fondo) -->
        <header
            class="absolute top-0 left-0 right-0 z-20 w-full px-6 sm:px-8 py-6 flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/cerberusLight.png') }}" alt="Cerberus Logo" loading="lazy"
                    class="h-12 w-auto">
                <h1 class="text-2xl font-semibold tracking-tight text-white">
                    Cerberus <span class="text-cerberus-light">2.0</span>
                </h1>
            </div>

            @if (Route::has('login'))
                <!-- Nav desktop -->
                <nav class="hidden sm:flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="px-5 py-2 bg-cerberus-primary hover:bg-cerberus-hover text-white rounded-md shadow transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-5 py-2 border border-white/30 hover:border-white/60
                                  text-white hover:bg-white/10
                                  rounded-md transition font-medium backdrop-blur-sm">
                            Iniciar sesión
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-5 py-2 bg-cerberus-light hover:bg-[#89C2D9]
                                      text-[#0D1B2A] rounded-md shadow transition font-medium">
                                Registrarse
                            </a>
                        @endif
                    @endauth
                </nav>

                <!-- Botón hamburguesa mobile -->
                <button type="button" @click="mobileNavOpen = !mobileNavOpen"
                    class="sm:hidden p-2 rounded-md border border-white/30 text-white"
                    aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!mobileNavOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileNavOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Nav mobile desplegable -->
                <nav x-show="mobileNavOpen" x-cloak x-transition
                    class="sm:hidden absolute top-full left-0 right-0 mt-2 mx-6 p-4 rounded-xl bg-cerberus-dark/95 border border-white/10 backdrop-blur-sm flex flex-col gap-3 text-sm">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="px-5 py-2 bg-cerberus-primary hover:bg-cerberus-hover text-white rounded-md shadow transition text-center">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-5 py-2 border border-white/30 hover:border-white/60 text-white hover:bg-white/10 rounded-md transition font-medium text-center">
                            Iniciar sesión
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-5 py-2 bg-cerberus-light hover:bg-[#89C2D9] text-[#0D1B2A] rounded-md shadow transition font-medium text-center">
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
                <!-- Badge de versión -->
                <div class="inline-block px-4 py-2 rounded-full border border-white/20 backdrop-blur-sm bg-white/5">
                    <span class="text-white/80 text-sm font-medium">🚀 Versión 2.0</span>
                </div>

                <!-- Título principal -->
                <h1 class="text-5xl md:text-7xl font-extrabold leading-tight text-white">
                    Gestión inteligente de tu
                    <span class="text-cerberus-light block mt-2">inventario tecnológico</span>
                </h1>

                <!-- Descripción -->
                <p class="text-xl text-white/80 max-w-3xl mx-auto leading-relaxed">
                    Cerberus 2.0 centraliza el control de activos tecnológicos en un entorno multiempresa:
                    asignaciones, préstamos, traslados y auditoría completa, con acceso diferenciado por roles.
                </p>

                <!-- Badges -->
                <div class="flex flex-wrap justify-center gap-3">
                    @foreach (['Multiempresa', 'Control por roles', 'Atributos dinámicos', 'Trazabilidad total', 'Exportación Excel & PDF'] as $badge)
                        <span
                            class="px-4 py-2 text-sm font-medium rounded-full
                                   bg-white/10 backdrop-blur-sm
                                   text-white border border-white/20">
                            {{ $badge }}
                        </span>
                    @endforeach
                </div>

                <!-- Botones CTA -->
                <div class="flex flex-wrap justify-center gap-4 pt-4">
                    <a href="{{ route('login') }}"
                        class="px-8 py-4 bg-cerberus-light hover:bg-[#89C2D9]
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

    <!-- SECCIÓN FEATURES - GRADIENTE AZUL -->
    <section id="features" class="py-24 w-full relative overflow-hidden">
        <!-- Gradiente azul -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#0A1628] via-[#1a3a5c] to-cerberus-dark"></div>
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-20 right-20 w-96 h-96 bg-cerberus-light/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 left-20 w-72 h-72 bg-cerberus-primary/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto text-center px-6">
            <div class="mb-16">
                <span
                    class="inline-block px-4 py-2 rounded-full bg-cerberus-light/10 border border-cerberus-light/20 text-cerberus-light text-sm font-medium mb-4 backdrop-blur-sm">
                    🚀 Características
                </span>
                <h3 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                    ¿Qué puede hacer <span class="text-cerberus-light">Cerberus</span>?
                </h3>
                <p class="text-gray-300 max-w-2xl mx-auto text-lg">Módulos disponibles en la plataforma</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ([['🖥️', 'Inventario de Equipos', 'Registra y clasifica activos tecnológicos con categorías configurables, estados, ubicaciones y atributos dinámicos por tipo de equipo (RAM, disco, S/N, etc.).'], ['📋', 'Asignaciones', 'Asigna equipos a usuarios o áreas comunes de forma permanente. Soporta periféricos vinculados al equipo principal con devoluciones independientes.'], ['🔄', 'Préstamos', 'Gestiona préstamos temporales con fechas de vencimiento, alertas de expiración, renovaciones y seguimiento de devoluciones.'], ['🚚', 'Traslados', 'Registra movimientos físicos de equipos entre ubicaciones con numeración automática (TRA-YYYY-NNN) y documentación del proceso.'], ['🔍', 'Auditoría', 'Cada acción queda registrada: quién la realizó, cuándo, y qué cambió. Trazabilidad completa para cumplimiento y control.'], ['⚙️', 'Configuración', 'Administra categorías, estados, ubicaciones, departamentos, cargos y empresas desde un panel centralizado.']] as [$icon, $title, $desc])
                    <div
                        class="group p-8 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:border-cerberus-light/40 hover:bg-white/10 transition-all duration-500 text-left hover:-translate-y-2 motion-reduce:hover:-translate-y-0 hover:shadow-2xl hover:shadow-cerberus-light/5">
                        <div
                            class="text-5xl mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500 motion-reduce:transform-none">
                            {{ $icon }}</div>
                        <h4
                            class="text-xl font-semibold mb-3 text-cerberus-light group-hover:text-white transition-colors duration-300">
                            {{ $title }}</h4>
                        <p
                            class="text-gray-400 text-sm leading-relaxed group-hover:text-gray-300 transition-colors duration-300">
                            {{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- SECCIÓN ROLES - Fondo claro para contraste -->
    <section class="py-24 w-full relative bg-cerberus-dark transition-colors duration-500">
        <div class="relative z-10 max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span
                    class="inline-block px-4 py-2 rounded-full bg-cerberus-primary/10 dark:bg-cerberus-light/10 border border-cerberus-primary/20 dark:border-cerberus-light/20 text-cerberus-primary dark:text-cerberus-light text-sm font-medium mb-4 backdrop-blur-sm">
                    🔐 Control de acceso
                </span>
                <h3
                    class="text-4xl md:text-5xl font-bold text-cerberus-dark dark:text-white transition-colors duration-500">
                    Acceso diferenciado por <span class="text-cerberus-primary dark:text-cerberus-light">roles</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ([['Administrador', 'Control total del sistema: configuración, usuarios, empresas y todos los datos del inventario.', 'border-cerberus-primary/20 dark:border-red-500/30 bg-cerberus-primary/5 dark:bg-red-500/5', '👑', 'text-cerberus-primary dark:text-red-400'], ['Analista', 'Gestión operativa de equipos, asignaciones, préstamos y traslados en su contexto de empresa.', 'border-cerberus-primary/20 dark:border-blue-500/30 bg-cerberus-primary/5 dark:bg-blue-500/5', '📊', 'text-cerberus-primary dark:text-blue-400'], ['Usuario', 'Visualización del inventario asignado y préstamos activos asociados a su perfil.', 'border-cerberus-primary/20 dark:border-green-500/30 bg-cerberus-primary/5 dark:bg-green-500/5', '👤', 'text-cerberus-primary dark:text-green-400']] as [$rol, $desc, $style, $emoji, $color])
                    <div
                        class="group p-8 rounded-2xl border {{ $style }} bg-white/80 dark:bg-transparent backdrop-blur-sm hover:-translate-y-3 motion-reduce:hover:-translate-y-0 transition-all duration-500 hover:shadow-xl">
                        <div class="text-5xl mb-4 group-hover:scale-110 transition-transform duration-500 motion-reduce:transform-none">
                            {{ $emoji }}</div>
                        <h4
                            class="font-bold text-2xl mb-3 {{ $color }} group-hover:text-cerberus-dark dark:group-hover:text-white transition-colors duration-300">
                            {{ $rol }}</h4>
                        <p
                            class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed group-hover:text-gray-800 dark:group-hover:text-white transition-colors duration-300">
                            {{ $desc }}</p>
                        <div
                            class="mt-6 h-0.5 w-12 bg-cerberus-primary/20 dark:bg-white/20 group-hover:w-full group-hover:bg-cerberus-primary dark:group-hover:bg-cerberus-light/50 transition-all duration-500 rounded-full">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA FINAL - Fondo oscuro intermedio -->
    <section class="py-24 w-full text-center relative bg-[#E8EEF2] dark:bg-cerberus-mid transition-colors duration-500">
        <div class="relative z-10 max-w-7xl mx-auto px-6">
            <div
                class="relative overflow-hidden rounded-3xl p-12 bg-gradient-to-r from-cerberus-primary/20 to-cerberus-primary/5 dark:from-cerberus-primary/30 dark:to-cerberus-light/30 border border-cerberus-primary/20 dark:border-cerberus-light/20 backdrop-blur-sm">
                <!-- Elementos decorativos -->
                <div
                    class="absolute -top-20 -right-20 w-64 h-64 bg-cerberus-primary/10 dark:bg-cerberus-light/10 rounded-full blur-3xl">
                </div>
                <div
                    class="absolute -bottom-20 -left-20 w-64 h-64 bg-cerberus-primary/5 dark:bg-cerberus-primary/10 rounded-full blur-3xl">
                </div>

                <div class="relative z-10">
                    <span
                        class="inline-block px-4 py-2 rounded-full bg-cerberus-primary/10 dark:bg-cerberus-light/10 border border-cerberus-primary/20 dark:border-cerberus-light/20 text-cerberus-primary dark:text-cerberus-light text-sm font-medium mb-6 backdrop-blur-sm">
                        🎯 Comienza ahora
                    </span>
                    <h3
                        class="text-4xl md:text-5xl font-bold mb-6 text-cerberus-dark dark:text-white transition-colors duration-500">
                        Controla tu infraestructura con <span class="text-cerberus-primary dark:text-cerberus-light">Cerberus</span>
                    </h3>
                    <p
                        class="text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto text-lg transition-colors duration-500">
                        Solución moderna, segura y multiempresa para la gestión completa del ciclo de vida de tus
                        activos tecnológicos.
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('login') }}"
                            class="group inline-flex items-center gap-2 px-10 py-5 bg-cerberus-primary hover:bg-cerberus-hover dark:bg-cerberus-light dark:hover:bg-[#89C2D9]
                              text-white dark:text-[#0D1B2A] rounded-lg font-semibold text-xl transition-all duration-300 shadow-lg
                              hover:scale-105 motion-reduce:transform-none hover:shadow-2xl hover:shadow-cerberus-primary/20 dark:hover:shadow-cerberus-light/20">
                            Comenzar ahora
                            <span class="group-hover:translate-x-1 transition-transform duration-300 motion-reduce:transform-none">→</span>
                        </a>
                        <a href="#features"
                            class="px-10 py-5 border-2 border-cerberus-primary/30 dark:border-white/30 hover:border-cerberus-primary/50 dark:hover:border-cerberus-light/50
                              text-cerberus-primary dark:text-white hover:text-cerberus-dark dark:hover:text-cerberus-light rounded-lg font-semibold text-xl transition-all duration-300
                              backdrop-blur-sm hover:scale-105 motion-reduce:transform-none hover:shadow-xl hover:bg-white/50 dark:hover:bg-white/5">
                            Explorar características
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER - GRADIENTE AZUL -->
    <footer class="relative w-full overflow-hidden">
        <!-- Gradiente azul -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#0A1628] via-[#1a3a5c] to-cerberus-dark"></div>
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 right-0 w-64 h-64 bg-cerberus-light/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-cerberus-primary/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 py-12 text-center text-gray-300 text-sm">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/cerberusLight.png') }}" alt="Cerberus Logo" loading="lazy"
                            class="h-8 w-auto opacity-70">
                        <span class="text-white/80 font-semibold">Cerberus 2.0</span>
                    </div>

                    <div class="flex gap-8 text-sm">
                        <a href="#" class="hover:text-cerberus-light transition-colors duration-300">Política de
                            privacidad</a>
                        <a href="#" class="hover:text-cerberus-light transition-colors duration-300">Términos de
                            uso</a>
                        <a href="#" class="hover:text-cerberus-light transition-colors duration-300">Contacto</a>
                    </div>

                    <div class="text-gray-400">
                        © {{ date('Y') }} R - A - H
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* Animaciones suaves */
        .animate-pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: translateY(0);
            }

            50% {
                opacity: 0.4;
                transform: translateY(4px);
            }
        }

        .animate-bounce {
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        /* Scroll suave */
        html {
            scroll-behavior: smooth;
        }

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
