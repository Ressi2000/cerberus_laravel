<div class="w-full max-w-md
            bg-white/40 dark:bg-white/10
            backdrop-blur-lg
            border border-gray-200 dark:border-white/10
            rounded-2xl shadow-2xl p-8
            transition-all duration-500">

    {{-- LOGO + TÍTULO --}}
    <div class="flex flex-col items-center mb-8">
        <picture>
            <source srcset="{{ Vite::asset('resources/images/cerberus.png') }}" media="(prefers-color-scheme: light)">
            <img src="{{ Vite::asset('resources/images/cerberusLight.png') }}"
                 alt="Cerberus Logo"
                 class="h-16 w-16 mb-3 transition-all duration-500">
        </picture>

        <h1 class="text-3xl font-bold tracking-tight text-[#0D1B2A] dark:text-white">
            Cerberus <span class="text-[#1E40AF] dark:text-[#A9D6E5]">2.0</span>
        </h1>
        <p class="text-gray-700 dark:text-gray-300 text-sm mt-1 text-center">
            Sistema de Inventario y Asignaciones Tecnológicas
        </p>
    </div>

    {{ $slot }}

    {{-- FOOTER --}}
    <p class="text-gray-600 dark:text-gray-400 text-xs mt-8 text-center">
        © {{ date('Y') }} Cerberus 2.0 — Sistema de Inventario y Asignaciones Tecnológicas
    </p>
</div>
