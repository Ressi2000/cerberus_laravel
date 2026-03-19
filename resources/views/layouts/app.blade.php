<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="cerberusDarkMode()" :class="{ 'dark': isDark }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Cerberus') }}</title>

    {{-- Anti-flash: aplica la clase dark ANTES de que el navegador pinte --}}
    <script>
        (function () {
            const saved = localStorage.getItem('cerberus-theme')
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
            if (saved === 'dark' || (saved === null && prefersDark)) {
                document.documentElement.classList.add('dark')
            }
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
            const toggleBtn = document.getElementById('mobile-sidebar-toggle')
            const closeBtn  = document.getElementById('sidebar-close')
            const sidebar   = document.getElementById('sidebar')
            const backdrop  = document.getElementById('sidebar-backdrop')

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

            document.querySelectorAll('.users-menu-toggle, .equipos-menu-toggle').forEach(toggle => {
                toggle.addEventListener('click', function () {
                    const menuId = this.classList.contains('users-menu-toggle') ? 'users-menu' : 'equipos-menu'
                    const menu = document.getElementById(menuId)
                    const icon = this.querySelector('.material-icons:last-child')
                    menu.classList.toggle('hidden')
                    if (icon) icon.style.transform = menu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)'
                })
            })

            document.querySelectorAll('#sidebar a').forEach(link => {
                link.addEventListener('click', () => { if (window.innerWidth < 1024) closeSidebar() })
            })
        })
    </script>

    @livewireScripts
</body>
</html>
