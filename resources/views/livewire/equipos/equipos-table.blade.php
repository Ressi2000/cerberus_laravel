<div class="space-y-6">

    {{-- STATS --}}
    <x-stats-cards :items="[
        ['title' => 'Total Equipos', 'value' => $total, 'icon' => 'inventory_2'],
        ['title' => 'Activos', 'value' => $activos, 'icon' => 'check_circle'],
        ['title' => 'En Mantenimiento', 'value' => $mantenimiento, 'icon' => 'build'],
        ['title' => 'Baja', 'value' => $baja, 'icon' => 'cancel'],
    ]" />

    {{-- HEADER --}}
    <x-crud-header title="Equipos" subtitle="Gestión del inventario corporativo" buttonLabel="Crear equipo"
        :buttonUrl="route('admin.equipos.create')">

        <x-slot name="filters">

            <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">

                @if ($this->activeFiltersCount > 0)
                    <div class="mb-3">
                        <span class="px-3 py-1 text-xs rounded-md bg-cerberus-primary/60 text-white">
                            Filtros activos: {{ $this->activeFiltersCount }}
                        </span>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-4">

                    {{-- SEARCH --}}
                    <div class="flex items-center flex-grow min-w-[220px]">
                        <div class="relative w-full">
                            <input type="text" wire:model.live.500ms="search" placeholder="Buscar equipos..."
                                class="w-full bg-cerberus-dark border border-cerberus-steel rounded-lg px-4 py-2 text-white">
                            <span class="material-icons absolute right-3 top-2.5 text-gray-400">search</span>
                        </div>
                    </div>

                    <x-select name="categoria_id" label="Categoría" :options="$categorias" wire:model.live="categoria_id" />

                    <x-select name="estado_id" label="Estado" :options="$estados" wire:model.live="estado_id" />

                </div>

                {{-- FILTROS EAV --}}
                @if ($atributosFiltrables->count())
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">

                        @foreach ($atributosFiltrables as $atributo)
                            @if ($atributo->tipo === 'boolean')
                                <x-select :name="'filtros.' . $atributo->id" :label="$atributo->nombre" :options="[1 => 'Sí', 0 => 'No']"
                                    wire:model.live="filtros.{{ $atributo->id }}" />
                            @elseif($atributo->tipo === 'select')
                                <x-select :name="'filtros.' . $atributo->id" :label="$atributo->nombre" :options="$atributo->opciones ?? []"
                                    wire:model.live="filtros.{{ $atributo->id }}" />
                            @else
                                <div>
                                    <label class="text-sm text-cerberus-light">
                                        {{ $atributo->nombre }}
                                    </label>
                                    <input type="text" wire:model.live.500ms="filtros.{{ $atributo->id }}"
                                        class="w-full bg-cerberus-dark border border-cerberus-steel rounded-lg px-3 py-2 text-white">
                                </div>
                            @endif
                        @endforeach

                    </div>
                @endif

                <div class="mt-4 flex justify-end">
                    <button wire:click="resetFilters"
                        class="bg-red-600/20 border border-red-700 text-red-300 px-3 py-2 rounded-lg hover:bg-red-700/40">
                        Limpiar filtros
                    </button>
                </div>

            </div>

        </x-slot>
    </x-crud-header>

    {{-- TABLA --}}
    <x-crud-table :headers="['Código', 'Categoría', 'Estado', 'Creado', 'Acciones']" :paginated="$equipos">

        @foreach ($equipos as $equipo)
            <tr wire:key="equipo-{{ $equipo->id }}" class="hover:bg-cerberus-darkest">

                <td class="px-4 py-3 text-white font-medium">
                    {{ $equipo->codigo_interno }}
                </td>

                <td class="px-4 py-3 text-cerberus-light">
                    {{ $equipo->categoria->nombre }}
                </td>

                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-md bg-cerberus-primary/20 text-cerberus-primary">
                        {{ $equipo->estado->nombre }}
                    </span>
                </td>

                <td class="px-4 py-3 text-cerberus-light">
                    {{ $equipo->created_at->format('d/m/Y') }}
                </td>

                <td class="px-6 py-4 text-center">
                    <x-table-actions :model="$equipo" :editUrl="route('admin.equipos.edit', $equipo)" viewEvent="openEquipoView"
                        deleteEvent="openEquipoDelete" deleteLabel="Eliminar" :policy="$equipo" />
                </td>

            </tr>
        @endforeach

    </x-crud-table>

</div>
