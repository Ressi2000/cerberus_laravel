<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="cerberusDarkMode()" :class="{ 'dark': isDark }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Cerberus') }}</title>

    {{--
        Anti-flash: aplica la clase dark ANTES de que el navegador pinte.
        Corre de forma síncrona, antes de cualquier CSS o JS.
        También lo reaplica en cada navegación SPA de Livewire.
    --}}
    <script>
        (function () {
            function applyTheme() {
                const saved = localStorage.getItem('cerberus-theme')
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
                const isDark = saved !== null ? saved === 'dark' : prefersDark
                document.documentElement.classList.toggle('dark', isDark)
            }

            // Aplicar en la carga inicial
            applyTheme()

            // Reaplicar en cada navegación SPA de Livewire (wire:navigate)
            document.addEventListener('livewire:navigated', applyTheme)
        })()
    </script>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-[#E2E8F0] dark:bg-cerberus-dark text-[#1E293B] dark:text-gray-100 antialiased transition-colors duration-200">

    <x-ui.sidebar />

    <div class="lg:ml-64">
        <x-ui.navbar :header="$header ?? 'Dashboard'" />

        <main class="p-4 mt-5 min-h-screen">
            @if (isset($title))
                <h1 class="text-2xl font-bold text-[#1E293B] dark:text-white mb-6">{{ $title }}</h1>
            @endif
            {{ $slot }}
        </main>
    </div>

    <x-ui.footer />

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setupSidebar()
            setupMenuToggles()
        })

        // Reinicializar tras navegación SPA de Livewire
        document.addEventListener('livewire:navigated', function () {
            setupSidebar()
            setupMenuToggles()
        })

        function setupSidebar() {
            const toggleBtn = document.getElementById('mobile-sidebar-toggle')
            const closeBtn  = document.getElementById('sidebar-close')
            const sidebar   = document.getElementById('sidebar')
            const backdrop  = document.getElementById('sidebar-backdrop')

            if (!sidebar) return

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full')
                backdrop.classList.remove('hidden')
                document.body.style.overflow = 'hidden'
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full')
                backdrop.classList.add('hidden')
                document.body.style.overflow = ''
            }

            toggleBtn?.addEventListener('click', openSidebar)
            closeBtn?.addEventListener('click', closeSidebar)
            backdrop?.addEventListener('click', closeSidebar)

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('-translate-x-full')
                    backdrop.classList.add('hidden')
                    document.body.style.overflow = ''
                }
            })

            document.querySelectorAll('#sidebar a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 1024) closeSidebar()
                })
            })
        }

        function setupMenuToggles() {
            document.querySelectorAll('.users-menu-toggle, .equipos-menu-toggle').forEach(toggle => {
                // Evitar duplicar listeners
                toggle.replaceWith(toggle.cloneNode(true))
            })

            document.querySelectorAll('.users-menu-toggle, .equipos-menu-toggle').forEach(toggle => {
                toggle.addEventListener('click', function () {
                    const menuId = this.classList.contains('users-menu-toggle') ? 'users-menu' : 'equipos-menu'
                    const menu = document.getElementById(menuId)
                    const icon = this.querySelector('.material-icons:last-child')
                    menu.classList.toggle('hidden')
                    if (icon) icon.style.transform = menu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)'
                })
            })
        }
    </script>

    @livewireScripts
</body>
</html>
