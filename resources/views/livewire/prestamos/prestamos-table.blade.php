<div class="space-y-6" x-data="{ tab: @entangle('tabActiva') }">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('prestamos.renovar-prestamo')
    @livewire('prestamos.prestamo-view-modal')

    {{-- ── Stats ───────────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Usuarios con préstamos', 'value' => $stats['usuarios_con_prestamos'], 'icon' => 'people'],
        ['title' => 'Áreas activas',          'value' => $stats['areas_activas'],          'icon' => 'corporate_fare'],
        ['title' => 'Equipos en préstamo',    'value' => $stats['equipos_en_prestamo'],    'icon' => 'swap_horiz'],
        ['title' => 'Préstamos vencidos',     'value' => $stats['vencidos'],               'icon' => 'schedule', 'color' => 'warning'],
        ['title' => 'Préstamos cerrados',     'value' => $stats['cerrados'],               'icon' => 'lock'],
    ]" />

    {{-- ── Header + Filtros ───────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Préstamos"
        subtitle="Control de equipos en préstamo temporal"
        buttonLabel="Nuevo préstamo"
        :buttonUrl="route('admin.prestamos.create')">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel
                        rounded-xl p-4 space-y-4">

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
                    placeholder="Nombre, cédula, ficha, empresa, departamento..."
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <x-form.select searchable label="Empresa"  :options="$this->empresasOpciones"  wire:model.live="empresa_id" />
                    <x-form.select searchable label="Analista" :options="$this->analistasOpciones" wire:model.live="analista_id" />
                    <x-form.input  type="date" label="Desde" wire:model.live="fecha_desde" />
                    <x-form.input  type="date" label="Hasta" wire:model.live="fecha_hasta" />
                </div>
            </div>
        </x-slot>

    </x-table.crud-header>

    {{-- ── Flash messages ─────────────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl
                    bg-emerald-50 dark:bg-emerald-900/20
                    border border-emerald-200 dark:border-emerald-700/40
                    text-emerald-700 dark:text-emerald-400 text-sm">
            <span class="material-icons text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Pestañas ────────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 p-1 bg-gray-100 dark:bg-cerberus-dark rounded-xl w-fit flex-wrap">

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
                {{ $stats['usuarios_con_prestamos'] }}
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

        <button @click="tab = 'cerrados'"
                :class="tab === 'cerrados'
                    ? 'bg-white dark:bg-cerberus-mid shadow-sm text-gray-900 dark:text-white'
                    : 'text-gray-500 dark:text-cerberus-steel hover:text-gray-700 dark:hover:text-cerberus-light'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150">
            <span class="material-icons text-base">lock</span>
            Cerrados
            <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold
                         bg-gray-200 dark:bg-cerberus-steel/40
                         text-gray-600 dark:text-cerberus-light">
                {{ $stats['cerrados'] }}
            </span>
        </button>

    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA: USUARIOS                                                    --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'usuarios'" x-cloak>
        <x-table.crud-table
            :headers="['Usuario', 'Empresa / Cargo', 'Sede', 'Equipos en préstamo', 'Último préstamo', 'Acciones']"
            :paginated="$usuarios">

            @forelse ($usuarios as $usuario)
                <tr wire:key="u-{{ $usuario->id }}"
                    class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $usuario->foto_url }}" alt="{{ $usuario->name }}"
                                 class="w-8 h-8 rounded-full object-cover flex-shrink-0
                                        border border-gray-200 dark:border-cerberus-steel/50">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $usuario->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-cerberus-steel truncate">
                                    {{ $usuario->cedula ?? '—' }} · Ficha: {{ $usuario->ficha ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-900 dark:text-white">{{ $usuario->empresaNomina?->nombre ?? '—' }}</p>
                        <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-0.5">{{ $usuario->cargo?->nombre ?? '—' }}</p>
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">
                        {{ $usuario->ubicacion?->nombre ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                     bg-amber-100 dark:bg-amber-900/30
                                     text-amber-700 dark:text-amber-400
                                     border border-amber-200 dark:border-amber-700/40">
                            <span class="material-icons text-xs">swap_horiz</span>
                            {{ $usuario->equipos_prestados_count ?? 0 }}
                            {{ ($usuario->equipos_prestados_count ?? 0) === 1 ? 'equipo' : 'equipos' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-cerberus-accent">
                        {{ $usuario->ultimo_prestamo
                            ? \Carbon\Carbon::parse($usuario->ultimo_prestamo)->format('d/m/Y')
                            : '—' }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        @php
                            $prestamosActivos = $usuario->prestamos()
                                ->whereIn('estado', ['Activo', 'Vencido'])
                                ->orderByDesc('fecha_prestamo')
                                ->get();
                            $prestamoActivo = $prestamosActivos->first();
                        @endphp
                        <x-table.table-actions :modelId="$usuario->id">
                            <x-slot name="acciones">
                                <li>
                                    <button wire:click="$dispatch('openPrestamoView', { userId: {{ $usuario->id }} })"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                   hover:text-[#1E40AF] dark:hover:text-white
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-[#1E40AF]/70 dark:text-cerberus-accent">visibility</span>
                                        Ver detalles
                                    </button>
                                </li>
                                <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>
                                @if ($prestamosActivos->isNotEmpty())
                                    @foreach ($prestamosActivos as $p)
                                        <li>
                                            <a href="{{ route('admin.prestamos.devolver', $p) }}"
                                               wire:navigate @click="close()"
                                               class="flex items-center gap-3 px-4 py-2.5 w-full
                                                      text-gray-600 dark:text-cerberus-light
                                                      hover:bg-amber-50 dark:hover:bg-amber-500/10
                                                      hover:text-amber-600 dark:hover:text-amber-400
                                                      transition-colors duration-100">
                                                <span class="material-icons text-base text-amber-500">keyboard_return</span>
                                                Devolver préstamo
                                                @if ($prestamosActivos->count() > 1)
                                                    <span class="ml-auto text-xs text-gray-400">{{ \Carbon\Carbon::parse($p->fecha_prestamo)->format('d/m/Y') }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                    <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>
                                    <li>
                                        <button wire:click="$dispatch('abrir-renovar', { prestamoId: {{ $prestamoActivo->id }} })"
                                                @click="close()"
                                                class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                       text-gray-600 dark:text-cerberus-light
                                                       hover:bg-blue-50 dark:hover:bg-blue-500/10
                                                       hover:text-blue-600 dark:hover:text-blue-400
                                                       transition-colors duration-100">
                                            <span class="material-icons text-base text-blue-500">event</span>
                                            Renovar préstamo
                                        </button>
                                    </li>
                                    <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>
                                    <li>
                                        <a href="{{ route('admin.prestamos.planilla.prestamo', $prestamoActivo) }}"
                                           target="_blank" @click="close()"
                                           class="flex items-center gap-3 px-4 py-2.5 w-full
                                                  text-gray-600 dark:text-cerberus-light
                                                  hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                  transition-colors duration-100">
                                            <span class="material-icons text-base text-cerberus-accent">download</span>
                                            Planilla de préstamo
                                        </a>
                                    </li>
                                @else
                                    <li class="px-4 py-2.5 text-xs text-gray-400 dark:text-cerberus-steel italic">
                                        Sin préstamos activos
                                    </li>
                                @endif
                                <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>
                                <li>
                                    <a href="{{ route('admin.prestamos.historial', $usuario) }}"
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
                                @if ($prestamosActivos->count() > 1)
                                    <li>
                                        <a href="{{ route('admin.prestamos.devolver.usuario', $usuario) }}"
                                           wire:navigate @click="close()"
                                           class="flex items-center gap-3 px-4 py-2.5 w-full
                                                  text-gray-600 dark:text-cerberus-light
                                                  hover:bg-amber-50 dark:hover:bg-amber-500/10
                                                  hover:text-amber-600 dark:hover:text-amber-400
                                                  transition-colors duration-100">
                                            <span class="material-icons text-base text-amber-500">keyboard_return</span>
                                            Devolver todos los equipos
                                        </a>
                                    </li>
                                @endif
                            </x-slot>
                        </x-table.table-actions>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center">
                        <span class="material-icons text-5xl text-gray-500 dark:text-cerberus-steel/30 block mb-3">people</span>
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">
                            {{ $search ? 'Sin resultados para "' . $search . '"' : 'Ningún usuario con préstamos activos.' }}
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
            :headers="['Área', 'Responsable', 'Equipos', 'Estado', 'Fecha préstamo', 'Analista', 'Acciones']"
            :paginated="$prestamosArea">

            @forelse ($prestamosArea as $prestamo)
                <tr wire:key="a-{{ $prestamo->id }}"
                    class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">

                    <td class="px-4 py-3">
                        <div class="flex items-start gap-2">
                            <span class="material-icons text-base text-cerberus-accent mt-0.5 flex-shrink-0">corporate_fare</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $prestamo->areaDepartamento?->nombre ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-0.5">
                                    {{ $prestamo->areaEmpresa?->nombre ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-900 dark:text-white">{{ $prestamo->areaResponsable?->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-0.5">{{ $prestamo->areaResponsable?->cargo?->nombre ?? '—' }}</p>
                    </td>

                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                     bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                                     border border-amber-200 dark:border-amber-700/40">
                            <span class="material-icons text-xs">swap_horiz</span>
                            {{ $prestamo->equipos_activos_count ?? 0 }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <x-prestamos.badge-estado :estado="$prestamo->estado" />
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">
                            {{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}
                        </p>
                        @if ($prestamo->fecha_devolucion_esperada)
                            <p class="text-xs mt-0.5 {{ $prestamo->estaVencido() ? 'text-red-500 font-semibold' : 'text-gray-400 dark:text-cerberus-steel' }}">
                                Devolver: {{ $prestamo->fecha_devolucion_esperada->format('d/m/Y') }}
                            </p>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">
                        {{ $prestamo->analista?->name ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        <x-table.table-actions :modelId="$prestamo->id">
                            <x-slot name="acciones">
                                <li>
                                    <a href="{{ route('admin.prestamos.devolver', $prestamo) }}"
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
                                    <button wire:click="$dispatch('abrir-renovar', { prestamoId: {{ $prestamo->id }} })"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-blue-50 dark:hover:bg-blue-500/10
                                                   hover:text-blue-600 dark:hover:text-blue-400
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-blue-500">event</span>
                                        Renovar préstamo
                                    </button>
                                </li>
                                <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>
                                <li>
                                    <a href="{{ route('admin.prestamos.planilla.prestamo', $prestamo) }}"
                                       target="_blank" @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                              transition-colors duration-100">
                                        <span class="material-icons text-base text-cerberus-accent">download</span>
                                        Planilla de préstamo
                                    </a>
                                </li>
                            </x-slot>
                        </x-table.table-actions>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-16 text-center">
                        <span class="material-icons text-5xl text-gray-500 dark:text-cerberus-steel/30 block mb-3">meeting_room</span>
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">
                            {{ $search ? 'Sin resultados para "' . $search . '"' : 'Sin préstamos a áreas comunes.' }}
                        </p>
                    </td>
                </tr>
            @endforelse

        </x-table.crud-table>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA: CERRADOS                                                    --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'cerrados'" x-cloak>
        <x-table.crud-table
            :headers="['Receptor', 'Tipo', 'Empresa', 'Equipos', 'Fecha préstamo', 'Analista', 'Planillas']"
            :paginated="$prestamosCerrados">

            @forelse ($prestamosCerrados as $prestamo)
                <tr wire:key="c-{{ $prestamo->id }}"
                    class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="material-icons text-base text-gray-500 dark:text-cerberus-steel flex-shrink-0">
                                {{ $prestamo->usuario_id ? 'person' : 'corporate_fare' }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $prestamo->receptorNombre() }}
                                </p>
                                @if ($prestamo->usuario?->cargo)
                                    <p class="text-xs text-gray-500 dark:text-cerberus-steel truncate">{{ $prestamo->usuario->cargo->nombre }}</p>
                                @elseif ($prestamo->areaEmpresa)
                                    <p class="text-xs text-gray-500 dark:text-cerberus-steel truncate">{{ $prestamo->areaEmpresa->nombre }}</p>
                                @endif
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3">
                        @if ($prestamo->usuario_id)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                         bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                         border border-blue-200 dark:border-blue-700/40">Personal</span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                         bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400
                                         border border-teal-200 dark:border-teal-700/40">Área</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">{{ $prestamo->empresa?->nombre ?? '—' }}</td>

                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-cerberus-accent">
                        {{ $prestamo->total_equipos_count ?? 0 }} equipo(s)
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">
                            {{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}
                        </p>
                        @if ($prestamo->fecha_devolucion_real)
                            <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                Devuelto: {{ $prestamo->fecha_devolucion_real->format('d/m/Y') }}
                            </p>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">{{ $prestamo->analista?->name ?? '—' }}</td>

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.prestamos.planilla.prestamo', $prestamo) }}"
                               target="_blank" title="Planilla de préstamo"
                               class="p-1.5 rounded-lg text-gray-500 dark:text-cerberus-steel
                                      hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                                      hover:text-cerberus-primary dark:hover:text-cerberus-accent transition">
                                <span class="material-icons text-base">download</span>
                            </a>
                            <a href="{{ route('admin.prestamos.planilla.devolucion', $prestamo) }}"
                               target="_blank" title="Planilla de devolución"
                               class="p-1.5 rounded-lg text-amber-500 dark:text-amber-400
                                      hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">
                                <span class="material-icons text-base">keyboard_return</span>
                            </a>
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-16 text-center">
                        <span class="material-icons text-5xl text-gray-500 dark:text-cerberus-steel/30 block mb-3">lock</span>
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">
                            {{ $search ? 'Sin resultados para "' . $search . '"' : 'No hay préstamos cerrados.' }}
                        </p>
                    </td>
                </tr>
            @endforelse

        </x-table.crud-table>
    </div>

</div>
