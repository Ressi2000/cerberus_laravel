{{--
    Sidebar Cerberus 2.0
    ─────────────────────────────────────────────────────────────────────────────
    El ancho (mini/full) lo controla el atributo data-sidebar en <html>
    junto con CSS inline en app.blade.php.
    CERO x-init, CERO const/let en Alpine — evita errores de evaluación.

    El logo cambia según la clase .dark en <html> (CSS dark: de Tailwind).
    ─────────────────────────────────────────────────────────────────────────────
--}}

<aside id="sidebar"
    class="fixed top-0 left-0 z-40 h-screen flex flex-col
           bg-white dark:bg-cerberus-mid
           border-r border-gray-200 dark:border-cerberus-steel/50
           shadow-sm dark:shadow-none
           lg:translate-x-0 -translate-x-full
           overflow-hidden"
    aria-label="Sidebar">

    {{-- ── LOGO ───────────────────────────────────────────────────────────── --}}
    <div class="flex items-center flex-shrink-0"
         style="height:56px; min-height:56px;">
        {{-- El color del borde se aplica con clase Tailwind --}}
        <div class="w-full h-full flex items-center
                    border-b border-gray-200 dark:border-cerberus-steel/50">

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-2.5 px-3 w-full min-w-0">

                {{-- Logo modo OSCURO --}}
                <img src="{{ Vite::asset('resources/images/cerberusLight.png') }}"
                     alt="Cerberus"
                     class="hidden dark:block flex-shrink-0 h-8 w-8 object-contain">

                {{-- Logo modo CLARO --}}
                <img src="{{ Vite::asset('resources/images/cerberus.png') }}"
                     alt="Cerberus"
                     class="block dark:hidden flex-shrink-0 h-8 w-8 object-contain">

                {{-- Texto — se oculta con CSS en modo mini --}}
                <div class="sidebar-logo-text flex flex-col min-w-0 whitespace-nowrap">
                    <span class="text-sm font-bold tracking-tight text-[#1E293B] dark:text-white leading-tight">
                        Cerberus
                        <span class="text-[#1E40AF] dark:text-cerberus-accent">2.0</span>
                    </span>
                    <span class="text-[10px] text-gray-400 dark:text-cerberus-steel leading-tight">
                        Inventario Tecnológico
                    </span>
                </div>
            </a>

            {{-- Cerrar — solo móvil --}}
            <button id="sidebar-close"
                class="lg:hidden flex-shrink-0 w-9 h-9 flex items-center justify-center mr-2
                       rounded-lg text-gray-400 dark:text-cerberus-steel
                       hover:text-gray-700 dark:hover:text-white
                       hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                       transition-colors duration-150">
                <span class="material-icons text-xl">close</span>
            </button>
        </div>
    </div>

    {{-- ── EMPRESA ACTIVA (solo Analista) ──────────────────────────────────── --}}
    @if (auth()->user()->hasRole('Analista') && auth()->user()->empresaActiva)
        <div class="flex-shrink-0 px-3 py-2
                    border-b border-gray-100 dark:border-cerberus-steel/30
                    overflow-hidden">
            <div class="sidebar-logo-text whitespace-nowrap">
                <p class="text-[10px] uppercase tracking-wider font-semibold
                           text-gray-400 dark:text-cerberus-accent">
                    Empresa activa
                </p>
                <p class="text-xs font-semibold text-[#1E293B] dark:text-white truncate mt-0.5">
                    {{ auth()->user()->empresaActiva->nombre }}
                </p>
            </div>
        </div>
    @endif

    {{-- ── NAVEGACIÓN ─────────────────────────────────────────────────────── --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 px-2 space-y-0.5">

        @php
            // Helpers para clases de items
            $active = fn(string ...$r) => request()->routeIs(...$r);

            $li = 'group relative flex items-center gap-3 rounded-lg px-3 py-2
                   transition-colors duration-150 w-full text-left cursor-pointer';

            $on  = 'bg-[#1E40AF]/10 dark:bg-cerberus-primary/20
                    text-[#1E40AF] dark:text-white';
            $off = 'text-gray-600 dark:text-gray-300
                    hover:bg-gray-100 dark:hover:bg-cerberus-steel/25
                    hover:text-[#1E40AF] dark:hover:text-white';

            $ion  = 'text-[#1E40AF] dark:text-cerberus-accent';
            $ioff = 'text-gray-400 dark:text-cerberus-steel
                     group-hover:text-[#1E40AF] dark:group-hover:text-cerberus-accent';
        @endphp

        {{-- ── DASHBOARD ──────────────────────────────────────────────────── --}}
        <a href="{{ route('dashboard') }}"
           class="{{ $li }} {{ $active('dashboard') ? $on : $off }}"
           title="Dashboard">

            <span class="material-icons text-xl flex-shrink-0
                         {{ $active('dashboard') ? $ion : $ioff }}">
                dashboard
            </span>
            <span class="sidebar-label text-sm font-medium whitespace-nowrap">
                Dashboard
            </span>

            {{-- Tooltip mini-sidebar --}}
            @include('components.ui._sidebar-tooltip', ['label' => 'Dashboard'])
        </a>

        {{-- ── USUARIOS (colapsable) ───────────────────────────────────────── --}}
        @php
            $usersOpen = $active('admin.usuarios.*', 'profile.*');
        @endphp

        <div x-data="{ open: {{ $usersOpen ? 'true' : 'false' }} }">

            <button @click="open = !open"
                    class="{{ $li }} {{ $usersOpen ? $on : $off }}"
                    title="Usuarios">

                <span class="material-icons text-xl flex-shrink-0
                             {{ $usersOpen ? $ion : $ioff }}">
                    group
                </span>

                <span class="sidebar-label flex-1 text-sm font-medium text-left whitespace-nowrap">
                    Usuarios
                </span>

                <span class="sidebar-arrow material-icons text-sm
                             text-gray-400 dark:text-cerberus-steel flex-shrink-0
                             transition-transform duration-200"
                      :class="{ 'rotate-180': open }">
                    expand_more
                </span>

                @include('components.ui._sidebar-tooltip', ['label' => 'Usuarios'])
            </button>

            <div class="sidebar-submenu mt-0.5 ml-4 pl-3
                        border-l-2 border-gray-200 dark:border-cerberus-steel/40
                        space-y-0.5 overflow-hidden"
                 x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1">

                <a href="{{ route('admin.usuarios.index') }}"
                   class="{{ $li }} py-1.5 text-sm
                          {{ $active('admin.usuarios.*') ? $on : $off }}">
                    <span class="material-icons text-base flex-shrink-0
                                 {{ $active('admin.usuarios.*') ? $ion : $ioff }}">
                        people_alt
                    </span>
                    <span class="whitespace-nowrap">Listado</span>
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="{{ $li }} py-1.5 text-sm
                          {{ $active('profile.*') ? $on : $off }}">
                    <span class="material-icons text-base flex-shrink-0
                                 {{ $active('profile.*') ? $ion : $ioff }}">
                        manage_accounts
                    </span>
                    <span class="whitespace-nowrap">Mi perfil</span>
                </a>
            </div>
        </div>

        {{-- ── EQUIPOS (colapsable) ────────────────────────────────────────── --}}
        @php $eqOpen = $active('admin.equipos.*'); @endphp

        <div x-data="{ open: {{ $eqOpen ? 'true' : 'false' }} }">

            <button @click="open = !open"
                    class="{{ $li }} {{ $eqOpen ? $on : $off }}"
                    title="Equipos">

                <span class="material-icons text-xl flex-shrink-0
                             {{ $eqOpen ? $ion : $ioff }}">
                    devices
                </span>

                <span class="sidebar-label flex-1 text-sm font-medium text-left whitespace-nowrap">
                    Equipos
                </span>

                <span class="sidebar-arrow material-icons text-sm
                             text-gray-400 dark:text-cerberus-steel flex-shrink-0
                             transition-transform duration-200"
                      :class="{ 'rotate-180': open }">
                    expand_more
                </span>

                @include('components.ui._sidebar-tooltip', ['label' => 'Equipos'])
            </button>

            <div class="sidebar-submenu mt-0.5 ml-4 pl-3
                        border-l-2 border-gray-200 dark:border-cerberus-steel/40
                        space-y-0.5"
                 x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1">

                <a href="{{ route('admin.equipos.index') }}"
                   class="{{ $li }} py-1.5 text-sm
                          {{ $active('admin.equipos.index') ? $on : $off }}">
                    <span class="material-icons text-base flex-shrink-0
                                 {{ $active('admin.equipos.index') ? $ion : $ioff }}">
                        inventory_2
                    </span>
                    <span class="whitespace-nowrap">Inventario</span>
                </a>

                @foreach ([
                    ['label' => 'Asignaciones', 'icon' => 'assignment_turned_in'],
                    ['label' => 'Préstamos',    'icon' => 'swap_horiz'],
                    ['label' => 'Mantenimientos','icon' => 'build'],
                ] as $item)
                    <span class="{{ $li }} py-1.5 text-sm opacity-50 cursor-not-allowed
                                  text-gray-500 dark:text-gray-400">
                        <span class="material-icons text-base flex-shrink-0 {{ $ioff }}">
                            {{ $item['icon'] }}
                        </span>
                        <span class="whitespace-nowrap">{{ $item['label'] }}</span>
                        <span class="sidebar-badge ml-auto text-[9px] px-1.5 py-0.5 rounded
                                     bg-amber-100 dark:bg-amber-900/30
                                     text-amber-600 dark:text-amber-400 font-medium whitespace-nowrap">
                            Pronto
                        </span>
                    </span>
                @endforeach
            </div>
        </div>

        {{-- ── AUDITORÍA ───────────────────────────────────────────────────── --}}
        <a href="{{ route('admin.auditoria.index') }}"
           class="{{ $li }} {{ $active('admin.auditoria.*') ? $on : $off }}"
           title="Auditoría">

            <span class="material-icons text-xl flex-shrink-0
                         {{ $active('admin.auditoria.*') ? $ion : $ioff }}">
                fact_check
            </span>
            <span class="sidebar-label text-sm font-medium whitespace-nowrap">
                Auditoría
            </span>

            @include('components.ui._sidebar-tooltip', ['label' => 'Auditoría'])
        </a>

    </nav>

    {{-- ── FOOTER DEL SIDEBAR ─────────────────────────────────────────────── --}}
    <div class="flex-shrink-0 border-t border-gray-200 dark:border-cerberus-steel/50 p-2">

        {{-- Versión (oculta en mini) --}}
        <p class="sidebar-logo-text text-[10px] text-gray-400 dark:text-cerberus-steel/60
                  text-center mb-2 whitespace-nowrap">
            Cerberus v2.0 · {{ date('Y') }}
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="{{ $li }} justify-center
                       text-red-500 dark:text-red-400
                       hover:bg-red-50 dark:hover:bg-red-500/10
                       hover:text-red-700 dark:hover:text-red-300"
                title="Cerrar sesión">
                <span class="material-icons text-xl flex-shrink-0">logout</span>
                <span class="sidebar-label text-sm font-medium whitespace-nowrap">
                    Cerrar sesión
                </span>
                @include('components.ui._sidebar-tooltip', ['label' => 'Cerrar sesión'])
            </button>
        </form>
    </div>
</aside>

{{-- Backdrop móvil --}}
<div id="sidebar-backdrop"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 lg:hidden hidden">
</div>