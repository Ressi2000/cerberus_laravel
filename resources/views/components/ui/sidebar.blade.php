<aside id="sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform duration-300
           bg-white dark:bg-cerberus-mid
           border-r border-gray-200 dark:border-cerberus-steel
           lg:translate-x-0 -translate-x-full"
    aria-label="Sidebar">

    {{-- Logo --}}
    <div class="h-16 flex items-center justify-between px-4
                border-b border-gray-200 dark:border-cerberus-steel
                bg-gray-50 dark:bg-cerberus-dark">
        <div class="flex items-center">
            <img src="{{ Vite::asset('resources/images/cerberusLight.png') }}" alt="Logo" class="h-8 w-auto">
            <span class="ml-2 text-lg font-semibold text-[#1E293B] dark:text-cerberus-light">CERBERUS</span>
        </div>
        <button id="sidebar-close" class="lg:hidden text-gray-400 dark:text-cerberus-light hover:text-gray-900 dark:hover:text-white">
            <span class="material-icons">close</span>
        </button>
    </div>

    {{-- Menú principal --}}
    <div class="flex flex-col justify-between h-[calc(100%-4rem)] px-3 py-4 overflow-y-auto">
        <ul class="space-y-1 font-medium">

            @php
                $linkBase = 'flex items-center p-2 rounded-lg transition-colors ';
                $linkActive = 'bg-[#1E40AF]/10 dark:bg-cerberus-steel text-[#1E40AF] dark:text-white';
                $linkIdle = 'text-gray-600 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-cerberus-steel';
                $iconBase = 'mr-3 ';
                $iconActive = 'text-[#1E40AF] dark:text-cerberus-light';
                $iconIdle = 'text-gray-400 dark:text-cerberus-light';
            @endphp

            {{-- Dashboard --}}
            <li>
                <a href="{{ route('dashboard') }}"
                    class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkActive : $linkIdle }}">
                    <span class="material-icons {{ $iconBase }} {{ request()->routeIs('dashboard') ? $iconActive : $iconIdle }}">dashboard</span>
                    <span>Dashboard</span>
                </a>
            </li>

            {{-- Auditoría --}}
            <li>
                <a href="{{ route('admin.auditoria.index') }}"
                    class="{{ $linkBase }} {{ request()->routeIs('admin.auditoria.*') ? $linkActive : $linkIdle }}">
                    <span class="material-icons {{ $iconBase }} {{ request()->routeIs('admin.auditoria.*') ? $iconActive : $iconIdle }}">fact_check</span>
                    <span>Auditoría</span>
                </a>
            </li>

            {{-- Gestión de usuarios --}}
            <li>
                <button type="button"
                    class="{{ $linkBase }} w-full {{ request()->routeIs('admin.usuarios.*') ? $linkActive : $linkIdle }} users-menu-toggle">
                    <span class="material-icons {{ $iconBase }} {{ request()->routeIs('admin.usuarios.*') ? $iconActive : $iconIdle }}">group</span>
                    <span class="flex-1 text-left">Gestión de Usuarios</span>
                    <span class="material-icons text-sm transition-transform {{ request()->routeIs('admin.usuarios.*') ? 'rotate-180' : '' }}">expand_more</span>
                </button>
                <ul id="users-menu" class="{{ request()->routeIs('admin.usuarios.*') ? '' : 'hidden' }} py-1 space-y-1">
                    <li>
                        <a href="{{ route('admin.usuarios.index') }}"
                            class="flex items-center p-2 pl-11 rounded-lg text-sm
                                   {{ request()->routeIs('admin.usuarios.index') ? $linkActive : $linkIdle }}">
                            <span class="material-icons mr-2 text-sm">people</span>
                            Usuarios
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center p-2 pl-11 rounded-lg text-sm
                                   {{ request()->routeIs('profile.*') ? $linkActive : $linkIdle }}">
                            <span class="material-icons mr-2 text-sm">person</span>
                            Perfil
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Equipos --}}
            <li>
                <button type="button"
                    class="{{ $linkBase }} w-full {{ request()->routeIs('admin.equipos.*') ? $linkActive : $linkIdle }} equipos-menu-toggle">
                    <span class="material-icons {{ $iconBase }} {{ request()->routeIs('admin.equipos.*') ? $iconActive : $iconIdle }}">devices</span>
                    <span class="flex-1 text-left">Equipos</span>
                    <span class="material-icons text-sm transition-transform {{ request()->routeIs('admin.equipos.*') ? 'rotate-180' : '' }}">expand_more</span>
                </button>
                <ul id="equipos-menu" class="{{ request()->routeIs('admin.equipos.*') ? '' : 'hidden' }} py-1 space-y-1">
                    <li>
                        <a href="{{ route('admin.equipos.index') }}"
                            class="flex items-center p-2 pl-11 rounded-lg text-sm
                                   {{ request()->routeIs('admin.equipos.index') ? $linkActive : $linkIdle }}">
                            <span class="material-icons mr-2 text-sm">inventory_2</span>
                            Inventario
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 rounded-lg text-sm {{ $linkIdle }}">
                            <span class="material-icons mr-2 text-sm">category</span>
                            Categorías
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 rounded-lg text-sm {{ $linkIdle }}">
                            <span class="material-icons mr-2 text-sm">assignment_turned_in</span>
                            Asignaciones
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 rounded-lg text-sm {{ $linkIdle }}">
                            <span class="material-icons mr-2 text-sm">swap_horiz</span>
                            Préstamos
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 rounded-lg text-sm {{ $linkIdle }}">
                            <span class="material-icons mr-2 text-sm">build</span>
                            Mantenimientos
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Configuración --}}
            <li>
                <a href="#" class="{{ $linkBase }} {{ $linkIdle }}">
                    <span class="material-icons {{ $iconBase }} {{ $iconIdle }}">settings</span>
                    <span>Configuración</span>
                </a>
            </li>

        </ul>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-3 border-t border-gray-200 dark:border-cerberus-steel">
            @csrf
            <button type="submit"
                class="w-full flex items-center justify-center gap-2
                       bg-[#1E40AF] hover:bg-[#1E3A8A]
                       py-2 rounded-lg text-white transition-colors">
                <span class="material-icons">logout</span>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>

{{-- Backdrop móvil --}}
<div id="sidebar-backdrop"
    class="fixed inset-0 bg-black/50 z-30 lg:hidden hidden"></div>
