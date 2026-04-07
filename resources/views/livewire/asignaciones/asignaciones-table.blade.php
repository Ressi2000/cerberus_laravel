{{--
    asignaciones-table.blade.php — con pestañas Usuarios / Áreas comunes
    El switch de pestaña es Alpine puro (sin round-trip al servidor).
    Ambas pestañas comparten la misma búsqueda reactiva de Livewire.
--}}
<div class="space-y-6" x-data="{ tab: 'usuarios' }">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('asignaciones.asignacion-view-modal')

    {{-- ── Stats ───────────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Usuarios con equipos',  'value' => $stats['usuarios_con_equipos'], 'icon' => 'people'],
        ['title' => 'Áreas comunes activas', 'value' => $stats['areas_activas'],        'icon' => 'corporate_fare'],
        ['title' => 'Equipos asignados',     'value' => $stats['equipos_activos'],      'icon' => 'devices'],
        ['title' => 'Asignaciones cerradas', 'value' => $stats['cerradas'],             'icon' => 'lock'],
    ]" />

    {{-- ── Header + Búsqueda ──────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Asignaciones"
        subtitle="Control de equipos asignados"
        buttonLabel="Nueva asignación"
        :buttonUrl="route('admin.asignaciones.create')">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel
                        rounded-xl p-4 space-y-3">

                @if ($this->activeFiltersCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-cerberus-primary/60 text-white">
                            {{ $this->activeFiltersCount }} filtro(s) activo(s)
                        </span>
                        <button wire:click="resetFilters"
                                class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                            <span class="material-icons text-xs">close</span>
                            Limpiar
                        </button>
                    </div>
                @endif

                <x-form.input
                    label="Buscar"
                    wire:model.live.400ms="search"
                    placeholder="Nombre, cédula, empresa, departamento..."
                />
            </div>
        </x-slot>

    </x-table.crud-header>

    {{-- ── Pestañas ────────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 p-1 bg-gray-100 dark:bg-cerberus-dark rounded-xl w-fit">

        <button @click="tab = 'usuarios'"
                :class="tab === 'usuarios'
                    ? 'bg-white dark:bg-cerberus-mid shadow-sm text-gray-900 dark:text-white'
                    : 'text-gray-500 dark:text-cerberus-steel hover:text-gray-700 dark:hover:text-cerberus-light'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150">
            <span class="material-icons text-base">people</span>
            Usuarios
            <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold
                         bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                         text-cerberus-primary dark:text-cerberus-accent">
                {{ $stats['usuarios_con_equipos'] }}
            </span>
        </button>

        <button @click="tab = 'areas'"
                :class="tab === 'areas'
                    ? 'bg-white dark:bg-cerberus-mid shadow-sm text-gray-900 dark:text-white'
                    : 'text-gray-500 dark:text-cerberus-steel hover:text-gray-700 dark:hover:text-cerberus-light'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150">
            <span class="material-icons text-base">corporate_fare</span>
            Áreas comunes
            <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold
                         bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                         text-cerberus-primary dark:text-cerberus-accent">
                {{ $stats['areas_activas'] }}
            </span>
        </button>

    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA: USUARIOS                                                    --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'usuarios'" x-cloak>

        <x-table.crud-table
            :headers="['Usuario', 'Empresa / Cargo', 'Sede', 'Equipos activos', 'Última asignación', 'Acciones']"
            :paginated="$usuarios">

            @forelse ($usuarios as $usuario)
                <tr wire:key="u-{{ $usuario->id }}"
                    class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">

                    {{-- Usuario --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $usuario->foto_url }}" alt="{{ $usuario->name }}"
                                 class="w-8 h-8 rounded-full object-cover flex-shrink-0
                                        border border-gray-200 dark:border-cerberus-steel/50">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $usuario->name }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel truncate">
                                    {{ $usuario->cedula ?? '—' }} · Ficha: {{ $usuario->ficha ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </td>

                    {{-- Empresa / Cargo --}}
                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $usuario->empresaNomina?->nombre ?? '—' }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                            {{ $usuario->cargo?->nombre ?? '—' }}
                        </p>
                    </td>

                    {{-- Sede --}}
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">
                        {{ $usuario->ubicacion?->nombre ?? '—' }}
                    </td>

                    {{-- Equipos activos --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                     bg-emerald-100 dark:bg-emerald-900/30
                                     text-emerald-700 dark:text-emerald-400
                                     border border-emerald-200 dark:border-emerald-700/40">
                            <span class="material-icons text-xs">devices</span>
                            {{ $usuario->equipos_activos_count ?? 0 }}
                            {{ ($usuario->equipos_activos_count ?? 0) === 1 ? 'equipo' : 'equipos' }}
                        </span>
                    </td>

                    {{-- Última asignación --}}
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-cerberus-accent">
                        {{ $usuario->ultima_asignacion
                            ? \Carbon\Carbon::parse($usuario->ultima_asignacion)->format('d/m/Y')
                            : '—' }}
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-3 text-center">
                        <x-table.table-actions :modelId="$usuario->id">
                            <x-slot name="acciones">

                                <li>
                                    <button @click="close()"
                                            wire:click="$dispatch('openAsignacionView', { userId: {{ $usuario->id }} })"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                   hover:text-[#1E40AF] dark:hover:text-white
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-[#1E40AF]/70 dark:text-cerberus-accent">
                                            assignment
                                        </span>
                                        Ver detalle actual
                                    </button>
                                </li>

                                <li>
                                    <a href="{{ route('admin.asignaciones.historial', $usuario) }}"
                                       wire:navigate @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                              hover:text-purple-600 dark:hover:text-purple-400
                                              transition-colors duration-100">
                                        <span class="material-icons text-base text-purple-500">history</span>
                                        Historial completo
                                    </a>
                                </li>

                                <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>

                                <li>
                                    <a href="{{ route('admin.asignaciones.devolver.usuario', $usuario) }}"
                                       wire:navigate @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-amber-50 dark:hover:bg-amber-500/10
                                              hover:text-amber-600 dark:hover:text-amber-400
                                              transition-colors duration-100">
                                        <span class="material-icons text-base text-amber-500">keyboard_return</span>
                                        Registrar devolución
                                    </a>
                                </li>

                                <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>

                                <li>
                                    <a href="{{ route('admin.asignaciones.planilla.egreso', $usuario) }}"
                                       target="_blank" @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-red-50 dark:hover:bg-red-500/10
                                              hover:text-red-600 dark:hover:text-red-400
                                              transition-colors duration-100">
                                        <span class="material-icons text-base text-red-500">description</span>
                                        Planilla de egreso
                                    </a>
                                </li>

                            </x-slot>
                        </x-table.table-actions>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center">
                        <span class="material-icons text-5xl text-gray-200 dark:text-cerberus-steel/30 block mb-3">
                            people_outline
                        </span>
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">
                            {{ $search ? 'Sin resultados para "' . $search . '"' : 'Ningún usuario con equipos asignados.' }}
                        </p>
                    </td>
                </tr>
            @endforelse

        </x-table.crud-table>

    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA: ÁREAS COMUNES                                               --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'areas'" x-cloak>

        <x-table.crud-table
            :headers="['Área', 'Responsable', 'Equipos activos', 'Fecha asignación', 'Analista', 'Acciones']"
            :paginated="$asignacionesArea">

            @forelse ($asignacionesArea as $asignacion)
                <tr wire:key="a-{{ $asignacion->id }}"
                    class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">

                    {{-- Área --}}
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-2">
                            <span class="material-icons text-base text-cerberus-accent mt-0.5 flex-shrink-0">
                                corporate_fare
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $asignacion->areaDepartamento?->nombre ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                    {{ $asignacion->areaEmpresa?->nombre ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </td>

                    {{-- Responsable --}}
                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $asignacion->areaResponsable?->name ?? '—' }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                            {{ $asignacion->areaResponsable?->cargo?->nombre ?? '—' }}
                        </p>
                    </td>

                    {{-- Equipos activos --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                     bg-emerald-100 dark:bg-emerald-900/30
                                     text-emerald-700 dark:text-emerald-400
                                     border border-emerald-200 dark:border-emerald-700/40">
                            <span class="material-icons text-xs">devices</span>
                            {{ $asignacion->equipos_activos_count ?? 0 }}
                            {{ ($asignacion->equipos_activos_count ?? 0) === 1 ? 'equipo' : 'equipos' }}
                        </span>
                    </td>

                    {{-- Fecha --}}
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-cerberus-accent">
                        {{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                    </td>

                    {{-- Analista --}}
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">
                        {{ $asignacion->analista?->name ?? '—' }}
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-3 text-center">
                        <x-table.table-actions :modelId="$asignacion->id">
                            <x-slot name="acciones">

                                {{-- Ver equipos de esta área --}}
                                <li>
                                    <button @click="close()"
                                            wire:click="$dispatch('openAreaView', { id: {{ $asignacion->id }} })"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                   hover:text-[#1E40AF] dark:hover:text-white
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-[#1E40AF]/70 dark:text-cerberus-accent">
                                            visibility
                                        </span>
                                        Ver equipos del área
                                    </button>
                                </li>

                                <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>

                                {{-- Devolución de área --}}
                                <li>
                                    <a href="{{ route('admin.asignaciones.devolver', $asignacion) }}"
                                       wire:navigate @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-amber-50 dark:hover:bg-amber-500/10
                                              hover:text-amber-600 dark:hover:text-amber-400
                                              transition-colors duration-100">
                                        <span class="material-icons text-base text-amber-500">keyboard_return</span>
                                        Registrar devolución
                                    </a>
                                </li>

                                <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>

                                {{-- Planilla de asignación --}}
                                <li>
                                    <a href="{{ route('admin.asignaciones.planilla.asignacion', $asignacion) }}"
                                       target="_blank" @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                              transition-colors duration-100">
                                        <span class="material-icons text-base text-cerberus-accent">download</span>
                                        Planilla de asignación
                                    </a>
                                </li>

                            </x-slot>
                        </x-table.table-actions>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center">
                        <span class="material-icons text-5xl text-gray-200 dark:text-cerberus-steel/30 block mb-3">
                            meeting_room
                        </span>
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">
                            {{ $search ? 'Sin resultados para "' . $search . '"' : 'Sin asignaciones a áreas comunes.' }}
                        </p>
                    </td>
                </tr>
            @endforelse

        </x-table.crud-table>

    </div>

</div>