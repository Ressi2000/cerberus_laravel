<div class="space-y-6">

    @livewire('configuracion.estados.estado-view-modal')
    @livewire('configuracion.estados.estado-modal')
    @livewire('configuracion.estados.estado-delete-modal')

    <x-ui.stats-cards :items="[
        ['title' => 'Total estados', 'value' => $this->total, 'icon' => 'flag'],
        ['title' => 'Con equipos', 'value' => $this->conEquipos, 'icon' => 'devices'],
        ['title' => 'Sin equipos', 'value' => $this->sinEquipos, 'icon' => 'unpublished'],
        ['title' => 'Total equipos', 'value' => \App\Models\Equipo::count(), 'icon' => 'inventory_2'],
    ]" />

    <x-table.crud-header title="Estados de Equipos" subtitle="Estados del ciclo de vida del inventario"
        buttonLabel="Nuevo estado" buttonEvent="openEstadoCrear">

        <x-slot name="filters">
            <div
                class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
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

                <x-form.input label="Buscar" wire:model.live.400ms="search" placeholder="Nombre del estado..."
                    hint="Ej: Disponible, En mantenimiento, Dado de baja..." />
            </div>
        </x-slot>
    </x-table.crud-header>

    <x-table.crud-table :headers="['Nombre', 'Equipos asignados', 'Acciones']" export exportRoute="export.estados" :filters="$this->filterParams" :paginated="$estados">

        @forelse ($estados as $estado)
            <tr wire:key="est-{{ $estado->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3 text-[#1E293B] dark:text-white font-medium text-sm">
                    {{ $estado->nombre }}
                </td>

                <td class="px-4 py-3 text-center">
                    @if ($estado->equipos_count > 0)
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-blue-50 dark:bg-cerberus-primary/15
                                     text-blue-700 dark:text-cerberus-accent
                                     border border-blue-200 dark:border-cerberus-primary/30">
                            <span class="material-icons text-xs">devices</span>
                            {{ $estado->equipos_count }}
                        </span>
                    @else
                        <span class="text-sm text-gray-400 dark:text-cerberus-steel">—</span>
                    @endif
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions :model="$estado" viewEvent="openEstadoVer" editEvent="openEstadoEditar"
                        deleteEvent="openEstadoEliminar" deleteLabel="Eliminar" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-4 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">flag</span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">No hay estados registrados.</p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>
</div>
