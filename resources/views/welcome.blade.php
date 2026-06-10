<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cerberus 2.0 – Sistema de Inventario y Asignaciones Tecnológicas</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen flex flex-col font-sans antialiased transition-colors duration-500
             bg-[#E2E8F0] dark:bg-[#0D1B2A]
             text-[#1E293B] dark:text-[#F1F5F9]">

    <!-- NAVBAR -->
    <header class="w-full px-8 py-5 flex justify-between items-center max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
            <picture>
                <source srcset="{{ asset('images/cerberus.png') }}" media="(prefers-color-scheme: light)">
                <img src="{{ asset('images/cerberusLight.png') }}" alt="Cerberus Logo" class="h-12 w-auto transition-all duration-500">
            </picture>
            <h1 class="text-2xl font-semibold tracking-tight text-[#0D1B2A] dark:text-white">
                Cerberus <span class="text-[#1E40AF] dark:text-[#A9D6E5]">2.0</span>
            </h1>
        </div>

        @if (Route::has('login'))
            <nav class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="px-5 py-2 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white rounded-md shadow transition">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="px-5 py-2 border border-[#1E40AF] dark:border-[#A9D6E5]
                              text-[#1E40AF] dark:text-[#A9D6E5]
                              hover:bg-[#1E40AF] dark:hover:bg-[#A9D6E5]
                              hover:text-white dark:hover:text-[#0D1B2A]
                              rounded-md transition font-medium">
                        Iniciar sesión
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="px-5 py-2 bg-[#1E40AF] hover:bg-[#1E3A8A]
                                  dark:bg-[#A9D6E5] dark:hover:bg-[#89C2D9]
                                  text-white dark:text-[#0D1B2A]
                                  rounded-md shadow transition font-medium">
                            Registrarse
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>

    <!-- HERO -->
    <section class="flex flex-col lg:flex-row items-center justify-between max-w-7xl mx-auto w-full px-8 py-20 reveal">
        <div class="max-w-xl text-center lg:text-left space-y-6 animate-slide-in">
            <h2 class="text-4xl md:text-5xl font-bold leading-tight animate-fade-in animate-delay-200">
                Gestión inteligente de tu
                <span class="text-[#1E40AF] dark:text-[#A9D6E5]">inventario tecnológico</span>
            </h2>
            <p class="text-gray-700 dark:text-gray-300 text-lg animate-fade-in animate-delay-400">
                Cerberus 2.0 centraliza el control de activos tecnológicos en un entorno multiempresa: asignaciones,
                préstamos, traslados y auditoría completa, con acceso diferenciado por roles.
            </p>

            <!-- Badges de características clave -->
            <div class="flex flex-wrap justify-center lg:justify-start gap-2 animate-fade-in animate-delay-500">
                @foreach(['Multiempresa', 'Control por roles', 'Atributos dinámicos', 'Trazabilidad total', 'Exportación Excel & PDF'] as $badge)
                    <span class="px-3 py-1 text-xs font-medium rounded-full
                                 bg-[#1E40AF]/10 dark:bg-[#A9D6E5]/10
                                 text-[#1E40AF] dark:text-[#A9D6E5]
                                 border border-[#1E40AF]/20 dark:border-[#A9D6E5]/20">
                        {{ $badge }}
                    </span>
                @endforeach
            </div>

            <div class="flex justify-center lg:justify-start gap-4 mt-6 animate-fade-in animate-delay-600">
                <a href="{{ route('login') }}"
                class="px-6 py-3 bg-[#1E40AF] hover:bg-[#1E3A8A]
                        dark:bg-[#A9D6E5] dark:hover:bg-[#89C2D9]
                        text-white dark:text-[#0D1B2A]
                        rounded-md font-semibold transition">
                    Iniciar sesión
                </a>
            </div>
        </div>

        <div class="mt-12 lg:mt-0 lg:ml-12 w-full lg:w-1/2 flex justify-center animate-fade-in animate-delay-400">
            <img src="{{ asset('images/cerberus.gif') }}" alt="Cerberus Preview"
                class="rounded-2xl shadow-2xl border border-gray-300/40 dark:border-gray-600/30 w-full max-w-md">
        </div>
    </section>


    <!-- FEATURES -->
    <section class="py-16 w-full bg-gray-100 dark:bg-white/5 transition-colors duration-500 reveal">
        <div class="max-w-6xl mx-auto text-center px-6">
            <h3 class="text-3xl font-semibold mb-3 text-[#1E40AF] dark:text-[#A9D6E5] reveal">
                ¿Qué puede hacer Cerberus?
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-10 reveal">Módulos disponibles en la plataforma</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ([
                    ['🖥️', 'Inventario de Equipos', 'Registra y clasifica activos tecnológicos con categorías configurables, estados, ubicaciones y atributos dinámicos por tipo de equipo (RAM, disco, S/N, etc.).'],
                    ['📋', 'Asignaciones', 'Asigna equipos a usuarios o áreas comunes de forma permanente. Soporta periféricos vinculados al equipo principal con devoluciones independientes.'],
                    ['🔄', 'Préstamos', 'Gestiona préstamos temporales con fechas de vencimiento, alertas de expiración, renovaciones y seguimiento de devoluciones.'],
                    ['🚚', 'Traslados', 'Registra movimientos físicos de equipos entre ubicaciones con numeración automática (TRA-YYYY-NNN) y documentación del proceso.'],
                    ['🔍', 'Auditoría', 'Cada acción queda registrada: quién la realizó, cuándo, y qué cambió. Trazabilidad completa para cumplimiento y control.'],
                    ['⚙️', 'Configuración', 'Administra categorías, estados, ubicaciones, departamentos, cargos y empresas desde un panel centralizado.'],
                ] as [$icon, $title, $desc])
                    <div class="p-6 bg-white/60 dark:bg-white/10 rounded-xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 reveal text-left">
                        <div class="text-3xl mb-3">{{ $icon }}</div>
                        <h4 class="text-lg font-semibold mb-2 text-[#1E40AF] dark:text-[#A9D6E5]">{{ $title }}</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>


    <!-- ROLES -->
    <section class="py-16 max-w-5xl mx-auto px-6 reveal">
        <h3 class="text-2xl font-semibold text-center mb-10 text-[#0D1B2A] dark:text-white">
            Acceso diferenciado por <span class="text-[#1E40AF] dark:text-[#A9D6E5]">roles</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            @foreach([
                ['Administrador', 'Control total del sistema: configuración, usuarios, empresas y todos los datos del inventario.', 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'],
                ['Analista', 'Gestión operativa de equipos, asignaciones, préstamos y traslados en su contexto de empresa.', 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'],
                ['Usuario', 'Visualización del inventario asignado y préstamos activos asociados a su perfil.', 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'],
            ] as [$rol, $desc, $style])
                <div class="p-5 rounded-xl border {{ $style }} reveal">
                    <h4 class="font-semibold text-lg mb-2 text-[#0D1B2A] dark:text-white">{{ $rol }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>


    <!-- CTA -->
    <section class="py-16 text-center max-w-5xl mx-auto px-6 reveal">
        <div class="bg-[#1E40AF]/5 dark:bg-[#A9D6E5]/5 border border-[#1E40AF]/20 dark:border-[#A9D6E5]/20 rounded-2xl p-10">
            <h3 class="text-3xl font-bold mb-4 text-[#0D1B2A] dark:text-white">
                Controla tu infraestructura con
                <span class="text-[#1E40AF] dark:text-[#A9D6E5]">Cerberus</span>
            </h3>
            <p class="text-gray-600 dark:text-gray-300 mb-8 max-w-xl mx-auto">
                Solución moderna, segura y multiempresa para la gestión completa del ciclo de vida de tus activos tecnológicos.
            </p>
            <a href="{{ route('login') }}"
            class="inline-block px-8 py-4 bg-[#1E40AF] hover:bg-[#1E3A8A]
                    dark:bg-[#A9D6E5] dark:hover:bg-[#89C2D9]
                    text-white dark:text-[#0D1B2A]
                    rounded-lg font-semibold text-lg transition shadow-lg">
                Acceder al sistema
            </a>
        </div>
    </section>


    <!-- FOOTER -->
    <footer class="border-t border-gray-300 dark:border-white/10 py-8 text-center text-gray-600 dark:text-gray-400 text-sm">
        © {{ date('Y') }} Cerberus 2.0 — Sistema de Inventario y Asignaciones Tecnológicas.<br>
        Desarrollado con Laravel + Breeze.
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const revealElements = document.querySelectorAll('.reveal');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target); // solo se anima una vez
                    }
                });
            }, {
                threshold: 0.2
            });

            revealElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>
