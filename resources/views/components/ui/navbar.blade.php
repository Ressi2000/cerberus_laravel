{{--
    Navbar Cerberus 2.0
    ───────────────────────────────────────────────────────────────────────────
    Recibe $header como prop desde x-app-layout:
        <x-app-layout header="Gestión de Equipos">
    
    El botón desktop-sidebar-toggle despacha window.cerberus:sidebar-toggle
    que captura app.blade.php para cambiar data-sidebar en <html>.
    ───────────────────────────────────────────────────────────────────────────
--}}
<nav class="sticky top-0 z-30 flex items-center justify-between w-full px-4
            bg-white/95 dark:bg-cerberus-mid/95
            border-b border-gray-200 dark:border-cerberus-steel/50
            backdrop-blur-md shadow-sm dark:shadow-none
            transition-colors duration-200"
     style="height: 56px; min-height: 56px;">

    {{-- ── IZQUIERDA ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 min-w-0">

        {{-- Toggle móvil --}}
        <button id="mobile-sidebar-toggle"
            class="lg:hidden flex items-center justify-center w-9 h-9 rounded-lg
                   text-gray-500 dark:text-cerberus-light
                   hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                   hover:text-gray-900 dark:hover:text-white
                   transition-colors duration-150 flex-shrink-0">
            <span class="material-icons text-xl">menu</span>
        </button>

        {{--
            Toggle mini-sidebar — SOLO desktop.
            onclick despacha el evento personalizado que captura app.blade.php.
            No usa Alpine para evitar problemas de evaluación.
        --}}
        <button id="desktop-sidebar-toggle"
            onclick="window.dispatchEvent(new Event('cerberus:sidebar-toggle'))"
            class="hidden lg:flex items-center justify-center w-9 h-9 rounded-lg
                   text-gray-500 dark:text-cerberus-light
                   hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                   hover:text-gray-900 dark:hover:text-white
                   transition-colors duration-150 flex-shrink-0">
            {{-- El ícono cambia vía JS en app.blade.php --}}
            <span class="material-icons text-xl" id="sidebar-toggle-icon">menu_open</span>
        </button>

        {{-- Separador --}}
        <div class="hidden sm:block w-px h-5 bg-gray-200 dark:bg-cerberus-steel/40 flex-shrink-0 mx-1"></div>

        {{-- Título del módulo actual --}}
        <h1 class="text-sm font-semibold text-[#1E293B] dark:text-white truncate">
            {{ $header }}
        </h1>
    </div>

    {{-- ── DERECHA ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-1 flex-shrink-0">

        {{-- Toggle dark/light (usa Alpine del body) --}}
        <button
            @click="toggle()"
            class="flex items-center justify-center w-9 h-9 rounded-lg
                   text-gray-500 dark:text-cerberus-light
                   hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                   hover:text-gray-900 dark:hover:text-white
                   transition-colors duration-150"
            :title="isDark ? 'Modo claro' : 'Modo oscuro'">
            <span class="material-icons text-lg" x-show="isDark"  style="display:none">light_mode</span>
            <span class="material-icons text-lg" x-show="!isDark">dark_mode</span>
        </button>

        {{-- Notificaciones --}}
        <button class="relative flex items-center justify-center w-9 h-9 rounded-lg
                       text-gray-500 dark:text-cerberus-light
                       hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                       hover:text-gray-900 dark:hover:text-white
                       transition-colors duration-150">
            <span class="material-icons text-lg">notifications_none</span>
            <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-500
                         ring-2 ring-white dark:ring-cerberus-mid"></span>
        </button>

        {{-- Separador --}}
        <div class="w-px h-5 bg-gray-200 dark:bg-cerberus-steel/40 mx-1"></div>

        {{-- Menú usuario (Alpine puro, sin x-init problemático) --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">

            <button @click="open = !open"
                class="flex items-center gap-2 px-2 py-1 rounded-lg
                       hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                       transition-colors duration-150 group">

                <div class="relative">
                    <img src="{{ Auth::user()->foto_url }}"
                         class="w-7 h-7 rounded-full object-cover
                                ring-2 ring-transparent group-hover:ring-[#1E40AF]/20
                                dark:group-hover:ring-cerberus-accent/20
                                transition-all duration-150">
                    <span class="absolute bottom-0 right-0 w-2 h-2 bg-green-400 rounded-full
                                 ring-1 ring-white dark:ring-cerberus-mid"></span>
                </div>

                <div class="hidden sm:block text-left">
                    <p class="text-xs font-semibold text-[#1E293B] dark:text-white leading-tight">
                        {{ Str::words(Auth::user()->name, 2, '') }}
                    </p>
                    <p class="text-[10px] text-gray-400 dark:text-cerberus-steel leading-none mt-0.5">
                        {{ Auth::user()->roles->first()?->name ?? 'Usuario' }}
                    </p>
                </div>

                <span class="hidden sm:block material-icons text-sm text-gray-400
                             dark:text-cerberus-steel transition-transform duration-200"
                      :class="{ 'rotate-180': open }">
                    expand_more
                </span>
            </button>

            {{-- Dropdown usuario --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                 class="absolute right-0 top-full mt-2 w-64 z-50
                        bg-white dark:bg-cerberus-mid
                        border border-gray-100 dark:border-cerberus-steel/60
                        rounded-xl shadow-xl dark:shadow-black/30 overflow-hidden"
                 style="display:none">

                {{-- Info usuario --}}
                <div class="px-4 py-3 border-b border-gray-100 dark:border-cerberus-steel/40
                             bg-gradient-to-br from-[#1E40AF]/5 to-transparent
                             dark:from-cerberus-primary/10 dark:to-transparent">
                    <div class="flex items-center gap-3">
                        <img src="{{ Auth::user()->foto_url }}"
                             class="w-10 h-10 rounded-full object-cover
                                    ring-2 ring-[#1E40AF]/20 dark:ring-cerberus-primary/30">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-[#1E293B] dark:text-white truncate">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-cerberus-accent truncate">
                                {{ Auth::user()->email }}
                            </p>
                            <span class="inline-flex items-center gap-1 mt-1
                                         text-[10px] px-1.5 py-0.5 rounded font-medium
                                         bg-[#1E40AF]/10 text-[#1E40AF]
                                         dark:bg-cerberus-primary/20 dark:text-cerberus-light">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                {{ Auth::user()->roles->first()?->name ?? 'Usuario' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Cambio de empresa (solo Analistas con varias empresas) --}}
                @if (auth()->user()->hasRole('Analista') && auth()->user()->empresasAsignadas->count() > 1)
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-cerberus-steel/40">
                        <p class="text-[10px] uppercase tracking-wider font-semibold
                                  text-gray-400 dark:text-cerberus-accent mb-2">
                            Empresa activa
                        </p>
                        <form method="POST" action="{{ route('empresa.switch') }}">
                            @csrf
                            <div class="relative">
                                <select name="empresa_id" onchange="this.form.submit()"
                                    class="w-full text-xs rounded-lg px-3 py-2 pr-8 appearance-none
                                           bg-gray-50 dark:bg-cerberus-dark
                                           border border-gray-200 dark:border-cerberus-steel
                                           text-[#1E293B] dark:text-white cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/20">
                                    @foreach (Auth::user()->empresasAsignadas as $empresa)
                                        <option value="{{ $empresa->id }}"
                                                @selected($empresa->id === Auth::user()->empresa_activa_id)>
                                            {{ $empresa->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="material-icons absolute right-2 top-1/2 -translate-y-1/2
                                             text-sm text-gray-400 pointer-events-none">
                                    unfold_more
                                </span>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Links --}}
                <div class="py-1">
                    <a href="{{ route('profile.edit') }}" @click="open = false"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm
                              text-gray-600 dark:text-cerberus-light
                              hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                              hover:text-[#1E40AF] dark:hover:text-white
                              transition-colors duration-100">
                        <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel">
                            manage_accounts
                        </span>
                        Mi perfil
                    </a>
                    <a href="{{ route('profile.activity') }}" @click="open = false"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm
                              text-gray-600 dark:text-cerberus-light
                              hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                              hover:text-[#1E40AF] dark:hover:text-white
                              transition-colors duration-100">
                        <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel">
                            history
                        </span>
                        Mi actividad
                    </a>
                </div>

                <div class="border-t border-gray-100 dark:border-cerberus-steel/40 py-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm
                                   text-red-600 dark:text-red-400
                                   hover:bg-red-50 dark:hover:bg-red-500/10
                                   transition-colors duration-100">
                            <span class="material-icons text-base">logout</span>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

{{-- Sincronizar ícono del toggle al cargar --}}
<script>
    (function () {
        var icon = document.getElementById('sidebar-toggle-icon');
        if (icon) {
            var mini = localStorage.getItem('cerberus_sidebar_mini') === 'true';
            icon.textContent = mini ? 'menu' : 'menu_open';
        }
    })();
</script>