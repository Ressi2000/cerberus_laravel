<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Cerberus') }}</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-cerberus-dark text-gray-100 antialiased">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Navbar -->
        <x-navbar :header="$header ?? 'Dashboard'" />

        <!-- Page Content -->
        <main class="p-4 mt-5 min-h-screen bg-cerberus-dark">
            @if (isset($title))
                <h1 class="text-2xl font-bold text-white mb-6">{{ $title }}</h1>
            @endif
            {{ $slot }}
        </main>
    </div>

    {{-- Footer --}}
    <x-footer />

    <!-- Scripts para la interactividad - VERSIÓN CORREGIDA -->
    
    <script>
        // Esperar a que el DOM esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del DOM
            const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
            const sidebarClose = document.getElementById('sidebar-close');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');

            console.log('Elementos cargados:', {
                mobileSidebarToggle,
                sidebarClose,
                sidebar,
                sidebarBackdrop
            });

            // Toggle sidebar en móvil
            function openSidebar() {
                console.log('Abriendo sidebar');
                sidebar.classList.remove('-translate-x-full');
                sidebarBackdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                console.log('Cerrando sidebar');
                sidebar.classList.add('-translate-x-full');
                sidebarBackdrop.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Event listeners - CON VERIFICACIÓN
            if (mobileSidebarToggle) {
                mobileSidebarToggle.addEventListener('click', openSidebar);
                console.log('Listener agregado al botón móvil');
            } else {
                console.error('No se encontró el botón mobile-sidebar-toggle');
            }

            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
                console.log('Listener agregado al botón cerrar');
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', closeSidebar);
                console.log('Listener agregado al backdrop');
            }

            // Cerrar sidebar al redimensionar
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebarBackdrop.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                } else {
                    // Solo cerrar si no está explícitamente abierto
                    if (!sidebar.classList.contains('-translate-x-full')) {
                        closeSidebar();
                    }
                }
            });

            // Funcionalidad de menús desplegables
            document.querySelectorAll('.users-menu-toggle, .equipos-menu-toggle').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const menuId = this.classList.contains('users-menu-toggle') ? 'users-menu' :
                        'equipos-menu';
                    const menu = document.getElementById(menuId);
                    const icon = this.querySelector('.material-icons:last-child');

                    menu.classList.toggle('hidden');
                    icon.style.transform = menu.classList.contains('hidden') ? 'rotate(0deg)' :
                        'rotate(180deg)';
                });
            });

            // Cerrar sidebar al hacer clic en un enlace en móvil
            document.querySelectorAll('#sidebar a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 1024) {
                        closeSidebar();
                    }
                });
            });

            // Debug: Verificar estado inicial
            console.log('Estado inicial del sidebar:', {
                translateClass: sidebar.classList.contains('-translate-x-full') ? 'oculto' : 'visible',
                width: window.innerWidth
            });
        });
    </script>
    
    @livewireScripts

</body>

</html>
