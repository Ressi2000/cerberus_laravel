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
                <source srcset="{{ Vite::asset('resources/images/cerberus.png') }}" media="(prefers-color-scheme: light)">
                <img src="{{ Vite::asset('resources/images/cerberusLight.png') }}" alt="Cerberus Logo" class="h-12 w-auto transition-all duration-500">
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
                Cerberus 2.0 es un sistema integral para controlar y administrar equipos, software, licencias,
                asignaciones, préstamos y mantenimientos en un entorno multiempresa, con trazabilidad completa y control
                por roles.
            </p>
            <div class="flex justify-center lg:justify-start gap-4 mt-6 animate-fade-in animate-delay-600">
                <a href="{{ route('login') }}"
                class="px-6 py-3 bg-[#1E40AF] hover:bg-[#1E3A8A]
                        dark:bg-[#A9D6E5] dark:hover:bg-[#89C2D9]
                        text-white dark:text-[#0D1B2A]
                        rounded-md font-semibold transition">
                    Iniciar sesión
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                    class="px-6 py-3 border border-[#1E40AF] dark:border-[#A9D6E5]
                            text-[#1E40AF] dark:text-[#A9D6E5]
                            hover:bg-[#1E40AF] dark:hover:bg-[#A9D6E5]
                            hover:text-white dark:hover:text-[#0D1B2A]
                            rounded-md font-semibold transition">
                        Registrarse
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-12 lg:mt-0 lg:ml-12 w-full lg:w-1/2 flex justify-center animate-fade-in animate-delay-400">
            <img src="{{ Vite::asset('resources/images/cerberus.gif') }}" alt="Cerberus Preview"
                class="rounded-2xl shadow-2xl border border-gray-300/40 dark:border-gray-600/30 w-full max-w-md">
        </div>
    </section>


    <!-- FEATURES -->
    <section class="py-16 w-full bg-gray-100 dark:bg-white/5 transition-colors duration-500 reveal">
        <div class="max-w-6xl mx-auto text-center px-6">
            <h3 class="text-3xl font-semibold mb-10 text-[#1E40AF] dark:text-[#A9D6E5] reveal">
                Módulos principales
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ([
                    ['Inventario de Equipos', 'Registra, clasifica y controla cada activo tecnológico, con estados, ubicaciones, garantías y vida útil.'],
                    ['Asignaciones y Préstamos', 'Administra asignaciones permanentes o préstamos temporales, con seguimiento de devoluciones y vencimientos.'],
                    ['Software y Licencias', 'Controla versiones, claves y vigencia de licencias instaladas por equipo o empresa.'],
                    ['Mantenimientos', 'Registra mantenimientos preventivos y correctivos, costos, fechas y técnicos responsables.'],
                    ['Movimientos', 'Controla traslados físicos entre ubicaciones o empresas con auditoría completa.'],
                    ['Auditoría y Seguridad', 'Cada acción se registra con usuario, fecha y cambios realizados, garantizando trazabilidad total.']
                ] as [$title, $desc])
                    <div class="p-6 bg-white/40 dark:bg-white/10 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 reveal">
                        <h4 class="text-xl font-semibold mb-3 text-[#1E40AF] dark:text-[#A9D6E5]">{{ $title }}</h4>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>


    <!-- CTA -->
    <section class="py-20 text-center max-w-5xl mx-auto px-6 reveal">
        <h3 class="text-3xl font-bold mb-6 text-[#0D1B2A] dark:text-white">
            Controla tu infraestructura tecnológica con 
            <span class="text-[#1E40AF] dark:text-[#A9D6E5]">Cerberus</span>
        </h3>
        <p class="text-gray-700 dark:text-gray-300 mb-8">
            Optimiza la administración, aumenta la trazabilidad y simplifica los procesos de soporte con una solución
            moderna, segura y escalable.
        </p>
        <a href="{{ route('register') }}"
        class="px-8 py-4 bg-[#1E40AF] hover:bg-[#1E3A8A]
                dark:bg-[#A9D6E5] dark:hover:bg-[#89C2D9]
                text-white dark:text-[#0D1B2A]
                rounded-lg font-semibold text-lg transition">
            Comenzar ahora
        </a>
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
