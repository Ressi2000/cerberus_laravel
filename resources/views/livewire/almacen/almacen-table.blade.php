<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('almacen.componente-modal')
    @livewire('almacen.movimiento-stock-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Componentes activos', 'value' => $this->totalComponentes,      'icon' => 'inventory_2'],
        ['title' => 'Unidades en stock',   'value' => $this->totalUnidadesEnStock,  'icon' => 'warehouse'],
        ['title' => 'Sin stock',           'value' => $this->totalSinStock,         'icon' => 'production_quantity_limits'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Almacén de Componentes"
        subtitle="Stock de piezas y repuestos reutilizables, por empresa"
        buttonLabel="Nuevo componente"
        buttonEvent="openComponenteCrear">

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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input
                        label="Buscar"
                        wire:model.live.400ms="search"
                        placeholder="Nombre o descripción..."
                    />

                    @can('viewAny', \App\Models\ComponenteAlmacen::class)
                        @if (Auth::user()->hasRole('Administrador'))
                            <x-form.select
                                label="Empresa"
                                placeholder="Todas"
                                :options="$this->empresasOpciones"
                                wire:model.live="empresa_id"
                            />
                        @endif
                    @endcan
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button
                        wire:click="$toggle('mostrar_inactivos')"
                        role="switch"
                        aria-checked="{{ $mostrar_inactivos ? 'true' : 'false' }}"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                               border-2 border-transparent transition-colors duration-200
                               {{ $mostrar_inactivos ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                     shadow ring-0 transition duration-200
                                     {{ $mostrar_inactivos ? 'translate-x-4' : 'translate-x-0' }}">
                        </span>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">
                        Mostrar desactivados
                    </span>
                </div>

            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Componente', 'Empresa', 'Unidad', 'Stock', 'Estado', 'Acciones']"
        :paginated="$this->componentes">

        @forelse ($this->componentes as $componente)
            <tr wire:key="comp-{{ $componente->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       {{ ! $componente->activo ? 'opacity-60 bg-gray-50 dark:bg-cerberus-dark/30' : '' }}
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3">
                    <p class="text-[#1E293B] dark:text-white font-medium text-sm">{{ $componente->nombre }}</p>
                    @if ($componente->descripcion)
                        <p class="text-gray-500 dark:text-cerberus-light text-xs truncate max-w-xs">{{ $componente->descripcion }}</p>
                    @endif
                </td>

                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">
                    {{ $componente->empresa->nombre ?? '—' }}
                </td>

                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">
                    {{ $componente->unidad }}
                </td>

                <td class="px-4 py-3 text-center">
                    <span @class([
                        'inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full border font-semibold',
                        'bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/30' => $componente->stock_actual === 0,
                        'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30' => $componente->stock_actual > 0,
                    ])>
                        {{ $componente->stock_actual }}
                    </span>
                </td>

                <td class="px-4 py-3">
                    @if ($componente->activo)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            <span class="material-icons text-xs">block</span> Desactivado
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions :model="$componente" editEvent="openComponenteEditar">
                        <x-slot name="acciones">
                            <li>
                                <button wire:click="$dispatch('openMovimientoStock', { componenteId: {{ $componente->id }} })"
                                        @click="close()"
                                        class="flex items-center gap-3 px-4 py-2.5 w-full
                                               text-gray-600 dark:text-cerberus-light
                                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                               hover:text-blue-600 dark:hover:text-cerberus-accent
                                               transition-colors duration-100">
                                    <span class="material-icons text-base text-cerberus-accent">swap_vert</span>
                                    Registrar movimiento de stock
                                </button>
                            </li>
                            @if ($componente->activo)
                                <li>
                                    <div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div>
                                </li>
                                <li>
                                    <button wire:click="desactivar({{ $componente->id }})"
                                            wire:confirm="¿Desactivar «{{ $componente->nombre }}»? Ya no se podrá usar en nuevos mantenimientos."
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-red-600 dark:text-red-400
                                                   hover:bg-red-50 dark:hover:bg-red-500/10
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base">block</span>
                                        Desactivar
                                    </button>
                                </li>
                            @else
                                <li>
                                    <div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div>
                                </li>
                                <li>
                                    <button wire:click="reactivar({{ $componente->id }})"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-green-600 dark:text-green-400
                                                   hover:bg-green-50 dark:hover:bg-green-500/10
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base">restart_alt</span>
                                        Reactivar
                                    </button>
                                </li>
                            @endif
                        </x-slot>
                    </x-table.table-actions>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-cerberus-steel">
                    No se encontraron componentes.
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>
