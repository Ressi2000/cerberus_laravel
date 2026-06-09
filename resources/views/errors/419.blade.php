<!DOCTYPE html>
<html lang="es" id="html-root">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión expirada — Cerberus 2.0</title>

    {{-- Anti-flash script --}}
    <script>
        (function () {
            const saved = localStorage.getItem('cerberus-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (saved === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans antialiased transition-colors duration-300
             bg-[#E2E8F0] dark:bg-[#0D1B2A]
             text-[#1E293B] dark:text-[#F1F5F9]
             flex items-center justify-center px-6">

    {{-- Toggle dark mode --}}
    <div class="fixed top-4 right-4 z-50">
        <button
            id="theme-toggle"
            onclick="toggleTheme()"
            class="w-9 h-9 flex items-center justify-center rounded-full
                   bg-white/60 dark:bg-white/10
                   border border-gray-300 dark:border-white/20
                   text-gray-700 dark:text-gray-200
                   hover:bg-white dark:hover:bg-white/20
                   shadow transition-all duration-200"
        >
            <span class="material-icons text-base" id="icon-sun" style="display:none">light_mode</span>
            <span class="material-icons text-base" id="icon-moon">dark_mode</span>
        </button>
    </div>

    {{-- Card principal --}}
    <div class="w-full max-w-md animate-fade-in">
        <div class="relative rounded-2xl overflow-hidden
                    bg-white/70 dark:bg-[#1B263B]/80
                    backdrop-blur-lg
                    border border-gray-200 dark:border-white/10
                    shadow-xl dark:shadow-[#0D1B2A]/60
                    p-8 sm:p-10">

            {{-- Franja de acento superior --}}
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#1E40AF] via-[#60A5FA] to-[#A9D6E5] rounded-t-2xl"></div>

            {{-- Logo --}}
            <div class="flex justify-center mb-6">
                <picture>
                    <source media="(prefers-color-scheme: dark)" srcset="{{ asset('images/cerberus.png') }}">
                    <img
                        src="{{ asset('images/cerberusLight.png') }}"
                        alt="Cerberus 2.0"
                        class="h-16 w-auto dark-logo"
                        id="cerberus-logo"
                    >
                </picture>
            </div>

            {{-- Icono de alerta --}}
            <div class="flex justify-center mb-5">
                <div class="w-16 h-16 rounded-full flex items-center justify-center
                            bg-amber-100 dark:bg-amber-500/10
                            border border-amber-300 dark:border-amber-500/30
                            shadow-inner">
                    <span class="material-icons text-3xl text-amber-500 dark:text-amber-400">timer_off</span>
                </div>
            </div>

            {{-- Código de error --}}
            <p class="text-center text-xs font-semibold tracking-widest uppercase
                       text-gray-400 dark:text-[#415A77] mb-1">
                Error 419
            </p>

            {{-- Título --}}
            <h1 class="text-center text-2xl font-semibold text-gray-800 dark:text-[#A9D6E5] mb-3">
                Sesión expirada
            </h1>

            {{-- Descripción --}}
            <p class="text-center text-sm text-gray-500 dark:text-[#778DA9] leading-relaxed mb-8">
                Tu sesión ha caducado o el token de seguridad ya no es válido.<br>
                Por favor, vuelve a iniciar sesión para continuar.
            </p>

            {{-- Botón de acción --}}
            <a
                href="{{ url('/') }}"
                class="flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-lg
                       bg-[#1E40AF] hover:bg-[#1E3A8A]
                       dark:bg-[#A9D6E5] dark:hover:bg-[#89C2D9] dark:text-[#0D1B2A]
                       text-white text-sm font-semibold tracking-wide
                       transition-all duration-200 shadow hover:shadow-md
                       focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50"
            >
                <span class="material-icons text-base">login</span>
                Volver al inicio de sesión
            </a>

            {{-- Footer --}}
            <p class="text-center text-xs text-gray-400 dark:text-[#415A77] mt-6">
                Cerberus 2.0 &mdash; Sistema de Inventario y Asignaciones Tecnológicas
            </p>
        </div>
    </div>

    <script>
        // Sincroniza el ícono con el tema actual al cargar
        (function () {
            const isDark = document.documentElement.classList.contains('dark');
            document.getElementById('icon-sun').style.display  = isDark ? 'inline' : 'none';
            document.getElementById('icon-moon').style.display = isDark ? 'none'   : 'inline';

            // Ajusta el logo según tema
            const logo = document.getElementById('cerberus-logo');
            if (logo) {
                logo.src = isDark
                    ? '{{ asset("images/cerberus.png") }}'
                    : '{{ asset("images/cerberusLight.png") }}';
            }
        })();

        function toggleTheme() {
            const html  = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('cerberus-theme', isDark ? 'dark' : 'light');

            document.getElementById('icon-sun').style.display  = isDark ? 'inline' : 'none';
            document.getElementById('icon-moon').style.display = isDark ? 'none'   : 'inline';

            const logo = document.getElementById('cerberus-logo');
            if (logo) {
                logo.src = isDark
                    ? '{{ asset("images/cerberus.png") }}'
                    : '{{ asset("images/cerberusLight.png") }}';
            }
        }
    </script>

</body>
</html>
