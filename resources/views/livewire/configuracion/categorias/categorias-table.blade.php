<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('configuracion.categorias.categoria-view-modal')
    @livewire('configuracion.categorias.categoria-modal')
    @livewire('configuracion.categorias.categoria-delete-modal')
    @livewire('configuracion.atributos.atributos-editor-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total categorías', 'value' => $this->total, 'icon' => 'category'],
        ['title' => 'Asignables', 'value' => $this->totalAsignables, 'icon' => 'assignment_turned_in'],
        ['title' => 'Con atributos', 'value' => $this->totalConAtributos, 'icon' => 'tune'],
        ['title' => 'Sin atributos', 'value' => $this->total - $this->totalConAtributos, 'icon' => 'tune_off'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header title="Categorías de Equipos" subtitle="Tipos de equipos disponibles en el inventario"
        buttonLabel="Nueva categoría" buttonEvent="openCategoriaCrear">

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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input label="Buscar" wire:model.live.400ms="search" placeholder="Nombre o descripción..."
                        hint="Filtra por nombre o descripción de la categoría." />
                    <x-form.select label="Asignable" :options="[1 => 'Sí', 0 => 'No']" wire:model.live="asignable" />
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table :headers="['Nombre', 'Descripción', 'Asignable', 'Atributos', 'Equipos', 'Acciones']" export exportRoute="export.categorias" :filters="$this->filterParams" :paginated="$categorias">

        @forelse ($categorias as $categoria)
            <tr wire:key="cat-{{ $categoria->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3 text-[#1E293B] dark:text-white font-medium text-sm">
                    {{ $categoria->nombre }}
                </td>

                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm max-w-xs truncate">
                    {{ $categoria->descripcion ?? '—' }}
                </td>

                <td class="px-4 py-3 text-center">
                    @if ($categoria->asignable)
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Sí
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/10 text-gray-500 dark:text-cerberus-steel
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            <span class="material-icons text-xs">remove_circle</span> No
                        </span>
                    @endif
                </td>

                {{-- Atributos: botón que abre el editor en bloque ──────────────── --}}
                <td class="px-4 py-3 text-center">
                    <button wire:click="$dispatch('openAtributosEditor', { categoriaId: {{ $categoria->id }} })"
                        title="Editar atributos de esta categoría"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium
                               transition group
                               {{ $categoria->atributos_count > 0
                                   ? 'bg-[#1E40AF]/10 dark:bg-cerberus-primary/15 text-[#1E40AF] dark:text-cerberus-accent hover:bg-[#1E40AF]/20 dark:hover:bg-cerberus-primary/25'
                                   : 'bg-gray-100 dark:bg-cerberus-steel/15 text-gray-500 dark:text-cerberus-steel hover:bg-gray-200 dark:hover:bg-cerberus-steel/30' }}">
                        <span class="material-icons text-sm">tune</span>
                        {{ $categoria->atributos_count }}
                        <span class="hidden group-hover:inline ml-0.5">editar</span>
                    </button>
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $categoria->equipos_count }}
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions :model="$categoria" viewEvent="openCategoriaVer"
                        editEvent="openCategoriaEditar" deleteEvent="openCategoriaEliminar" deleteLabel="Eliminar" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center">
                    <span
                        class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">category</span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">No hay categorías registradas.</p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>
</div>
