<nav class="sticky top-0 z-30 w-full
            bg-white/80 dark:bg-cerberus-mid
            border-b border-gray-200 dark:border-cerberus-steel
            backdrop-blur-sm
            px-4 py-3 flex items-center justify-between shadow-md
            transition-colors duration-200">

    {{-- Botón menú móvil + título --}}
    <div class="flex items-center gap-3">
        <button id="mobile-sidebar-toggle"
            class="text-gray-500 dark:text-cerberus-light hover:text-gray-900 dark:hover:text-white lg:hidden">
            <span class="material-icons text-2xl">menu</span>
        </button>
        <h1 class="text-lg font-semibold text-[#1E293B] dark:text-cerberus-light">
            {{ $header ?? 'Panel de Control' }}
        </h1>
    </div>

    {{-- Acciones --}}
    <div class="flex items-center gap-4">

        {{-- Toggle dark mode --}}
        <button
            @click="toggle()"
            class="w-9 h-9 flex items-center justify-center rounded-full
                   bg-gray-100 dark:bg-cerberus-steel/30
                   text-gray-600 dark:text-cerberus-light
                   hover:bg-gray-200 dark:hover:bg-cerberus-steel/60
                   transition-all duration-200"
            :title="isDark ? 'Modo claro' : 'Modo oscuro'"
        >
            <span class="material-icons text-base" x-show="isDark" style="display:none">light_mode</span>
            <span class="material-icons text-base" x-show="!isDark">dark_mode</span>
        </button>

        {{-- Notificaciones --}}
        <button type="button" class="relative">
            <span class="material-icons text-gray-500 dark:text-cerberus-light hover:text-gray-900 dark:hover:text-white">
                notifications
            </span>
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                3
            </span>
        </button>

        {{-- Menú de usuario --}}
        <button id="user-menu-button"
            data-dropdown-toggle="user-dropdown"
            class="flex items-center gap-2 text-sm font-medium
                   text-gray-700 dark:text-cerberus-light
                   hover:text-gray-900 dark:hover:text-white
                   focus:outline-none">
            <img src="{{ Auth::user()->foto_url }}"
                class="w-8 h-8 rounded-full border border-gray-300 dark:border-cerberus-steel">
            <span class="hidden sm:block">{{ Auth::user()->name ?? 'Usuario' }}</span>
            <span class="material-icons text-base">expand_more</span>
        </button>

        {{-- Dropdown usuario --}}
        <div id="user-dropdown"
            class="hidden z-50 w-56
                   bg-white dark:bg-cerberus-mid
                   border border-gray-200 dark:border-cerberus-steel
                   rounded-xl shadow-lg overflow-hidden">

            {{-- Info usuario --}}
            <div class="px-4 py-3 border-b border-gray-200 dark:border-cerberus-steel">
                <p class="text-sm font-semibold text-[#1E293B] dark:text-white">
                    {{ Auth::user()->name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent truncate">
                    {{ Auth::user()->email }}
                </p>
            </div>

            {{-- Cambio de empresa activa (solo Analistas con múltiples empresas) --}}
            @if (auth()->user()->hasRole('Analista') && auth()->user()->empresasAsignadas->count() > 1)
                <div class="px-4 py-3 border-b border-gray-200 dark:border-cerberus-steel">
                    <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-cerberus-accent mb-2">
                        Empresa activa
                    </p>
                    <form method="POST" action="{{ route('empresa.switch') }}">
                        @csrf
                        <select name="empresa_id" onchange="this.form.submit()"
                            class="w-full text-sm rounded-lg px-3 py-2
                                   bg-gray-100 dark:bg-cerberus-dark
                                   text-[#1E293B] dark:text-white
                                   border border-gray-300 dark:border-cerberus-steel
                                   focus:ring-cerberus-primary focus:border-cerberus-primary">
                            @foreach (Auth::user()->empresasAsignadas as $empresa)
                                <option value="{{ $empresa->id }}"
                                    @selected($empresa->id === Auth::user()->empresa_activa_id)>
                                    {{ $empresa->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            {{-- Acciones --}}
            <ul class="py-2 text-sm text-gray-600 dark:text-cerberus-light">
                <li>
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center gap-2 px-4 py-2
                               hover:bg-gray-100 dark:hover:bg-cerberus-steel transition">
                        <span class="material-icons text-base">person</span>
                        Perfil
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full flex items-center gap-2 px-4 py-2 text-left
                                       hover:bg-gray-100 dark:hover:bg-cerberus-steel transition">
                            <span class="material-icons text-base">logout</span>
                            Cerrar sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
