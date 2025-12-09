<nav class="sticky top-0 z-30 w-full bg-cerberus-mid border-b border-cerberus-steel px-4 py-3 flex items-center justify-between shadow-md">

    {{-- Botón menú (móvil) --}}
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen"
                class="text-cerberus-light hover:text-white lg:hidden"
                id="mobile-sidebar-toggle">
            <span class="material-icons text-2xl">menu</span>
        </button>
        <h1 class="text-lg font-semibold text-cerberus-light">{{ $header ?? 'Panel de Control' }}</h1>
    </div>

    {{-- Acciones --}}
    <div class="flex items-center gap-6">
        {{-- Notificaciones --}}
        <button type="button" class="relative">
            <span class="material-icons text-cerberus-light hover:text-white">notifications</span>
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">3</span>
        </button>

        {{-- Menú de usuario --}}
        <button id="user-menu-button" data-dropdown-toggle="user-dropdown"
                class="flex items-center gap-2 text-sm font-medium text-cerberus-light hover:text-white focus:outline-none">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'User') }}&background=1E40AF&color=fff"
                 class="w-8 h-8 rounded-full border border-cerberus-steel">
            <span>{{ Auth::user()->name ?? 'Usuario' }}</span>
            <span class="material-icons text-base">expand_more</span>
        </button>

        {{-- Dropdown --}}
        <div id="user-dropdown" class="hidden z-50 bg-cerberus-mid border border-cerberus-steel text-sm rounded-lg shadow-lg w-44">
            <div class="px-4 py-2 text-cerberus-light border-b border-cerberus-steel">
                <p class="font-medium">{{ Auth::user()->name ?? 'Usuario' }}</p>
                <p class="text-xs text-cerberus-accent">{{ Auth::user()->email ?? '' }}</p>
            </div>
            <ul class="py-1 text-cerberus-light">
                <li><a href="" class="block px-4 py-2 hover:bg-cerberus-steel">Perfil</a></li>
                <li><form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="block w-full text-left px-4 py-2 hover:bg-cerberus-steel">Cerrar sesión</button>
                </form></li>
            </ul>
        </div>
    </div>
</nav>
