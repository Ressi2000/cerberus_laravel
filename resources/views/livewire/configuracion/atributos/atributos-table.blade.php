<div class="space-y-6">

    @livewire('configuracion.atributos.atributo-view-modal')
    @livewire('configuracion.atributos.atributo-modal')
    @livewire('configuracion.atributos.atributo-delete-modal')
    @livewire('configuracion.atributos.atributos-editor-modal') {{-- ← Editor en bloque --}}

    <x-ui.stats-cards :items="[
        ['title' => 'Total atributos', 'value' => $this->total, 'icon' => 'tune'],
        ['title' => 'Requeridos', 'value' => $this->requeridos, 'icon' => 'star'],
        ['title' => 'Filtrables', 'value' => $this->filtrables, 'icon' => 'filter_list'],
        ['title' => 'Categorías', 'value' => \App\Models\CategoriaEquipo::count(), 'icon' => 'category'],
    ]" />

    <x-table.crud-header title="Atributos EAV" subtitle="Características técnicas por categoría de equipo"
        buttonLabel="Nuevo atributo" buttonEvent="openAtributoCrear">

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

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-form.input label="Buscar" wire:model.live.400ms="search" placeholder="Nombre del atributo..." />
                    <x-form.select label="Categoría" :options="$categorias" wire:model.live="categoria_id"
                        hint="Filtra atributos de una categoría específica." />
                    <x-form.select label="Tipo de dato" :options="$tipos" wire:model.live="tipo" />
                </div>

                {{-- Acceso rápido: editar en bloque por categoría ─────────────── --}}
                @if (count($categorias) > 0)
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-cerberus-steel/30">
                        <span class="text-xs text-gray-500 dark:text-cerberus-light flex-shrink-0">
                            Editar en bloque:
                        </span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($categorias as $catId => $catNombre)
                                <button
                                    wire:click="$dispatch('openAtributosEditor', { categoriaId: {{ $catId }} })"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-lg
                                           bg-[#1E40AF]/10 dark:bg-cerberus-primary/15
                                           text-[#1E40AF] dark:text-cerberus-accent
                                           border border-[#1E40AF]/20 dark:border-cerberus-primary/30
                                           hover:bg-[#1E40AF]/20 dark:hover:bg-cerberus-primary/25
                                           transition">
                                    <span class="material-icons text-xs">tune</span>
                                    {{ $catNombre }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </x-slot>
    </x-table.crud-header>

    <x-table.crud-table :headers="['Categoría', 'Atributo', 'Tipo', 'Req.', 'Filtrable', 'En tabla', 'Orden', 'Acciones']" export exportRoute="export.atributos" :filters="$this->filterParams" :paginated="$atributos">

        @forelse ($atributos as $atributo)
            <tr wire:key="attr-{{ $atributo->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3">
                    {{-- Click en la categoría abre el editor en bloque ────────── --}}
                    <button
                        wire:click="$dispatch('openAtributosEditor', { categoriaId: {{ $atributo->categoria_id }} })"
                        title="Editar todos los atributos de esta categoría"
                        class="px-2 py-0.5 text-xs rounded-full transition
                               bg-[#1E40AF]/10 dark:bg-cerberus-primary/20
                               text-[#1E40AF] dark:text-cerberus-accent
                               border border-[#1E40AF]/20 dark:border-cerberus-primary/30
                               hover:bg-[#1E40AF]/20 dark:hover:bg-cerberus-primary/30">
                        {{ $atributo->categoria->nombre }}
                    </button>
                </td>

                <td class="px-4 py-3 text-[#1E293B] dark:text-white font-medium text-sm">
                    {{ $atributo->nombre }}
                    @if ($atributo->tipo === 'select' && count($atributo->opciones ?? []) > 0)
                        <span class="ml-1 text-xs text-gray-400 dark:text-cerberus-steel font-normal">
                            ({{ count($atributo->opciones) }} opciones)
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3">
                    @php
                        $badge = [
                            'string' => ['Texto', 'text_fields', 'blue'],
                            'text' => ['Texto +', 'notes', 'blue'],
                            'integer' => ['Entero', 'tag', 'purple'],
                            'decimal' => ['Decimal', 'tag', 'purple'],
                            'boolean' => ['Sí/No', 'toggle_on', 'green'],
                            'date' => ['Fecha', 'calendar_today', 'yellow'],
                            'select' => ['Lista', 'list', 'orange'],
                        ][$atributo->tipo] ?? [$atributo->tipo, 'help', 'gray'];
                        $colors = [
                            'blue' =>
                                'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30',
                            'purple' =>
                                'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-500/30',
                            'green' =>
                                'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
                            'yellow' =>
                                'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-500/30',
                            'orange' =>
                                'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/30',
                            'gray' =>
                                'bg-gray-50 dark:bg-cerberus-steel/10 text-gray-500 dark:text-cerberus-steel border-gray-200 dark:border-cerberus-steel/30',
                        ][$badge[2]];
                    @endphp
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full border {{ $colors }}">
                        <span class="material-icons text-xs">{{ $badge[1] }}</span>
                        {{ $badge[0] }}
                    </span>
                </td>

                @foreach (['requerido', 'filtrable', 'visible_en_tabla'] as $campo)
                    <td class="px-4 py-3 text-center">
                        @if ($atributo->$campo)
                            <span
                                class="material-icons text-green-500 dark:text-green-400 text-base">check_circle</span>
                        @else
                            <span
                                class="material-icons text-gray-300 dark:text-cerberus-steel/40 text-base">radio_button_unchecked</span>
                        @endif
                    </td>
                @endforeach

                <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-cerberus-light">
                    {{ $atributo->orden }}
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions :model="$atributo" viewEvent="openAtributoVer" editEvent="openAtributoEditar"
                        deleteEvent="openAtributoEliminar" deleteLabel="Eliminar" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-4 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">tune</span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">
                        No hay atributos registrados.
                        @if ($this->activeFiltersCount > 0)
                            Prueba ajustando los filtros.
                        @endif
                    </p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>
</div>
