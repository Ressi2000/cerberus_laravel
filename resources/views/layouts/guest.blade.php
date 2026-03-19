<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="cerberusDarkMode()" :class="{ 'dark': isDark }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Cerberus 2.0') }}</title>

    {{-- Anti-flash --}}
    <script>
        (function () {
            const saved = localStorage.getItem('cerberus-theme')
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
            if (saved === 'dark' || (saved === null && prefersDark)) {
                document.documentElement.classList.add('dark')
            }
        })()
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans antialiased transition-colors duration-200
             bg-[#E2E8F0] dark:bg-[#0D1B2A]
             text-[#1E293B] dark:text-[#F1F5F9]
             flex items-center justify-center px-6">

    {{-- Botón toggle dark mode (esquina superior derecha) --}}
    <div class="fixed top-4 right-4 z-50">
        <button
            @click="toggle()"
            class="w-9 h-9 flex items-center justify-center rounded-full
                   bg-white/60 dark:bg-white/10
                   border border-gray-300 dark:border-white/20
                   text-gray-700 dark:text-gray-200
                   hover:bg-white dark:hover:bg-white/20
                   shadow transition-all duration-200"
            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        >
            {{-- Sol (modo claro visible) --}}
            <span class="material-icons text-base" x-show="isDark" style="display:none">light_mode</span>
            {{-- Luna (modo oscuro visible) --}}
            <span class="material-icons text-base" x-show="!isDark">dark_mode</span>
        </button>
    </div>

    {{ $slot }}

</body>
</html>
