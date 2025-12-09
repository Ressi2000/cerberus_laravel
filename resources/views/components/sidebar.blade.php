<aside id="sidebar"
       class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform duration-300 bg-cerberus-mid border-r border-cerberus-steel lg:translate-x-0 -translate-x-full"
       aria-label="Sidebar">

    {{-- Logo --}}
    <div class="h-16 flex items-center justify-between px-4 border-b border-cerberus-steel bg-cerberus-dark">
        <div class="flex items-center">
            <img src="{{ Vite::asset('resources/images/cerberusLight.png') }}" alt="Logo" class="h-8 w-auto">
            <span class="ml-2 text-lg font-semibold text-cerberus-light">CERBERUS</span>
        </div>
        <button id="sidebar-close" class="lg:hidden text-cerberus-light hover:text-white">
            <span class="material-icons">close</span>
        </button>
    </div>

    {{-- Menú principal --}}
    <div class="flex flex-col justify-between h-[calc(100%-4rem)] px-3 py-4 overflow-y-auto">
        <ul class="space-y-2 font-medium">

            {{-- Dashboard --}}
            <li>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center p-2 text-gray-200 rounded-lg hover:bg-cerberus-steel transition-colors">
                    <span class="material-icons mr-3 text-cerberus-light">dashboard</span>
                    <span>Dashboard</span>
                </a>
            </li>

            {{-- Gestión de usuarios --}}
            <li>
                <button type="button"
                        class="flex items-center w-full p-2 text-gray-200 rounded-lg hover:bg-cerberus-steel transition-colors users-menu-toggle">
                    <span class="material-icons mr-3 text-cerberus-light">group</span>
                    <span class="flex-1 text-left">Gestión de Usuarios</span>
                    <span class="material-icons text-sm text-cerberus-light transition-transform">expand_more</span>
                </button>
                <ul id="users-menu" class="hidden py-2 space-y-1 transition-all duration-200">
                    <li>
                        <a href="{{ route('admin.usuarios.index') }}"
                           class="flex items-center p-2 pl-11 rounded-lg hover:bg-cerberus-steel transition-colors">
                            <span class="material-icons mr-2 text-sm">people</span>
                            Usuarios
                        </a>
                    </li>
                    <li>
                        <a href="#"
                           class="flex items-center p-2 pl-11 rounded-lg hover:bg-cerberus-steel transition-colors">
                            <span class="material-icons mr-2 text-sm">person</span>
                            Perfil
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Equipos --}}
            <li>
                <button type="button"
                        class="flex items-center w-full p-2 text-gray-200 rounded-lg hover:bg-cerberus-steel transition-colors equipos-menu-toggle">
                    <span class="material-icons mr-3 text-cerberus-light">devices</span>
                    <span class="flex-1 text-left">Equipos</span>
                    <span class="material-icons text-sm text-cerberus-light transition-transform">expand_more</span>
                </button>
                <ul id="equipos-menu" class="hidden py-2 space-y-1 transition-all duration-200">
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 hover:bg-cerberus-steel rounded-lg transition-colors">
                            <span class="material-icons mr-2 text-sm">inventory_2</span>
                            Inventario
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 hover:bg-cerberus-steel rounded-lg transition-colors">
                            <span class="material-icons mr-2 text-sm">assignment</span>
                            Asignaciones
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 hover:bg-cerberus-steel rounded-lg transition-colors">
                            <span class="material-icons mr-2 text-sm">swap_horiz</span>
                            Préstamos
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 hover:bg-cerberus-steel rounded-lg transition-colors">
                            <span class="material-icons mr-2 text-sm">build</span>
                            Mantenimientos
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Configuración --}}
            <li>
                <a href="#"
                   class="flex items-center p-2 text-gray-200 rounded-lg hover:bg-cerberus-steel transition-colors">
                    <span class="material-icons mr-3 text-cerberus-light">settings</span>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-3 border-t border-cerberus-steel">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-cerberus-primary hover:bg-cerberus-hover
                           py-2 rounded-lg text-white transition-colors">
                <span class="material-icons">logout</span> 
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>

{{-- Fondo oscuro móvil --}}
<div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-30 lg:hidden hidden"></div>