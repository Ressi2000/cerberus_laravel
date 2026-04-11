<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('configuracion.categorias.categoria-view-modal')
    @livewire('configuracion.categorias.categoria-modal')
    @livewire('configuracion.categorias.categoria-delete-modal')
    @livewire('configuracion.atributos.atributos-editor-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total activas',   'value' => $this->total,             'icon' => 'category'],
        ['title' => 'Asignables',      'value' => $this->totalAsignables,   'icon' => 'assignment_turned_in'],
        ['title' => 'Con atributos',   'value' => $this->totalConAtributos, 'icon' => 'tune'],
        ['title' => 'Inactivas',       'value' => $this->totalInactivas,    'icon' => 'block'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Categorías de Equipos"
        subtitle="Tipos de equipos disponibles en el inventario"
        buttonLabel="Nueva categoría"
        buttonEvent="openCategoriaCrear">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">

                {{-- Badge de filtros activos --}}
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
                        hint="Filtra por nombre o descripción de la categoría."
                    />
                    <x-form.select
                        label="Asignable"
                        :options="['' => 'Todas', '1' => 'Sí', '0' => 'No']"
                        wire:model.live="asignable"
                    />
                </div>

                {{-- Toggle: mostrar inactivas --}}
                <div class="flex items-center gap-3 pt-1">
                    <button
                        wire:click="$toggle('mostrar_inactivas')"
                        role="switch"
                        aria-checked="{{ $mostrar_inactivas ? 'true' : 'false' }}"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                               border-2 border-transparent transition-colors duration-200
                               {{ $mostrar_inactivas ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                     shadow ring-0 transition duration-200
                                     {{ $mostrar_inactivas ? 'translate-x-4' : 'translate-x-0' }}">
                        </span>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">
                        Mostrar inactivas
                        @if ($this->totalInactivas > 0)
                            <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs
                                         bg-gray-100 dark:bg-cerberus-steel/40
                                         text-gray-500 dark:text-cerberus-light">
                                {{ $this->totalInactivas }}
                            </span>
                        @endif
                    </span>
                </div>

            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Nombre', 'Estado', 'Descripción', 'Asignable', 'Atributos', 'Equipos', 'Acciones']"
        export
        exportRoute="export.categorias"
        :filters="$this->filterParams"
        :paginated="$this->categorias">

        @forelse ($this->categorias as $categoria)
            <tr wire:key="cat-{{ $categoria->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       {{ ! $categoria->activo ? 'opacity-60 bg-gray-50 dark:bg-cerberus-dark/30' : '' }}
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                {{-- Nombre --}}
                <td class="px-4 py-3 text-[#1E293B] dark:text-white font-medium text-sm">
                    {{ $categoria->nombre }}
                </td>

                {{-- Badge activo / inactivo --}}
                <td class="px-4 py-3">
                    @if ($categoria->activo)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Activa
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            <span class="material-icons text-xs">block</span> Inactiva
                        </span>
                    @endif
                </td>

                {{-- Descripción --}}
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm max-w-xs truncate">
                    {{ $categoria->descripcion ?? '—' }}
                </td>

                {{-- Asignable --}}
                <td class="px-4 py-3 text-center">
                    @if ($categoria->asignable)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Sí
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/10 text-gray-500 dark:text-cerberus-steel
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            <span class="material-icons text-xs">remove_circle</span> No
                        </span>
                    @endif
                </td>

                {{-- Atributos --}}
                <td class="px-4 py-3 text-center">
                    @if ($categoria->activo)
                        <button
                            wire:click="$dispatch('openAtributosEditor', { categoriaId: {{ $categoria->id }} })"
                            title="Editar atributos"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition
                                   {{ $categoria->atributos_count > 0
                                       ? 'bg-cerberus-primary/20 text-cerberus-accent hover:bg-cerberus-primary/30 border border-cerberus-primary/30'
                                       : 'bg-gray-50 dark:bg-cerberus-steel/10 text-gray-400 dark:text-cerberus-steel hover:bg-cerberus-steel/20 border border-dashed border-gray-300 dark:border-cerberus-steel/30' }}">
                            <span class="material-icons text-xs">tune</span>
                            {{ $categoria->atributos_count }}
                        </button>
                    @else
                        <span class="text-gray-400 dark:text-cerberus-steel text-xs">{{ $categoria->atributos_count }}</span>
                    @endif
                </td>

                {{-- Equipos --}}
                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $categoria->equipos_count }}
                </td>

                {{-- Acciones --}}
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1">

                        @if ($categoria->activo)
                            {{-- Ver --}}
                            <button
                                wire:click="$dispatch('openCategoriaVer', { id: {{ $categoria->id }} })"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-cerberus-light
                                       hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-cerberus-steel/40 transition"
                                title="Ver detalle">
                                <span class="material-icons text-base">visibility</span>
                            </button>
                            {{-- Editar --}}
                            <button
                                wire:click="$dispatch('openCategoriaEditar', { id: {{ $categoria->id }} })"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-cerberus-light
                                       hover:text-cerberus-accent hover:bg-cerberus-steel/40 transition"
                                title="Editar">
                                <span class="material-icons text-base">edit</span>
                            </button>
                            {{-- Desactivar --}}
                            <button
                                wire:click="$dispatch('openCategoriaEliminar', { id: {{ $categoria->id }} })"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-cerberus-light
                                       hover:text-yellow-500 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition"
                                title="Desactivar">
                                <span class="material-icons text-base">block</span>
                            </button>

                        @else
                            {{-- REACTIVAR --}}
                            <button
                                wire:click="$dispatch('reactivarCategoria', { id: {{ $categoria->id }} })"
                                wire:confirm="¿Reactivar la categoría «{{ $categoria->nombre }}»?"
                                class="p-1.5 rounded-lg text-green-500 hover:text-green-400
                                       hover:bg-green-50 dark:hover:bg-green-900/20 transition"
                                title="Reactivar">
                                <span class="material-icons text-base">restart_alt</span>
                            </button>
                        @endif

                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-cerberus-steel">
                    No se encontraron categorías.
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>