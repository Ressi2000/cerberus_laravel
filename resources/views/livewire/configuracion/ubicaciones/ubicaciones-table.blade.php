<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('configuracion.ubicaciones.ubicacion-view-modal')
    @livewire('configuracion.ubicaciones.ubicacion-modal')
    @livewire('configuracion.ubicaciones.ubicacion-delete-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total',      'value' => $this->total,          'icon' => 'location_on'],
        ['title' => 'Locales',    'value' => $this->totalLocales,   'icon' => 'business'],
        ['title' => 'Foráneas',   'value' => $this->totalForaneas,  'icon' => 'public'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Ubicaciones"
        subtitle="Sedes físicas donde operan usuarios y equipos"
        buttonLabel="Nueva ubicación"
        buttonEvent="openUbicacionCrear">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">

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

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-form.input
                        label="Buscar"
                        wire:model.live.400ms="search"
                        placeholder="Nombre o descripción..."
                        hint="Filtra por nombre o descripción de la ubicación."
                    />
                    <x-form.select
                        label="Empresa"
                        :options="$empresas"
                        wire:model.live="empresa_id"
                    />
                    <x-form.select
                        label="Tipo"
                        :options="['' => 'Todas', '0' => 'Locales', '1' => 'Foráneas']"
                        wire:model.live="es_estado"
                        hint="Foráneas son visibles para todos los analistas."
                    />
                </div>

            </div>
        </x-slot>

    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Nombre', 'Descripción', 'Empresa', 'Tipo', 'Usuarios', 'Equipos', 'Acciones']"
        :paginated="$ubicaciones">

        @forelse ($ubicaciones as $ubicacion)
            <tr wire:key="ubic-{{ $ubicacion->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3 text-[#1E293B] dark:text-white font-medium text-sm">
                    {{ $ubicacion->nombre }}
                </td>

                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm max-w-xs truncate">
                    {{ $ubicacion->descripcion ?? '—' }}
                </td>

                <td class="px-4 py-3 text-gray-600 dark:text-cerberus-light text-sm">
                    {{ $ubicacion->empresa?->nombre ?? '—' }}
                </td>

                <td class="px-4 py-3">
                    @if ($ubicacion->es_estado)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-purple-50 dark:bg-purple-500/15
                                     text-purple-700 dark:text-purple-400
                                     border border-purple-200 dark:border-purple-500/30">
                            <span class="material-icons text-xs">public</span> Foránea
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/10
                                     text-gray-600 dark:text-cerberus-steel
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            <span class="material-icons text-xs">business</span> Local
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $ubicacion->usuarios_count }}
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $ubicacion->equipos_count }}
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions
                        :model="$ubicacion"
                        viewEvent="openUbicacionVer"
                        editEvent="openUbicacionEditar"
                        deleteEvent="openUbicacionEliminar"
                        deleteLabel="Eliminar"
                    />
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">
                        location_off
                    </span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">
                        No hay ubicaciones registradas.
                    </p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>