<div class="space-y-6" wire:init="loadData">
    <x-crud-header title="Usuarios" subtitle="Gestión de usuarios del sistema" buttonLabel="Crear usuario"
        :buttonUrl="route('admin.usuarios.create')">

        <x-slot name="filters">

            {{-- TOP BARRAS + FILTROS --}}
            <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">

                {{-- BADGE DE FILTROS ACTIVOS --}}
                @if ($this->activeFiltersCount > 0)
                    <div class="mb-3">
                        <span class="px-3 py-1 text-xs rounded-md bg-cerberus-primary/60 text-white">
                            Filtros activos: {{ $this->activeFiltersCount }}
                        </span>
                    </div>
                @endif

                {{-- FILA 1 --}}
                <div class="flex flex-wrap items-center gap-4">

                    {{-- SEARCH --}}
                    <div class="flex items-center flex-grow min-w-[200px]">
                        <div class="relative w-full">
                            <input type="text" wire:model.live.500ms="search" placeholder="Buscar usuarios..."
                                class="w-full bg-cerberus-dark border border-cerberus-steel rounded-lg px-4 py-2 text-white">
                            <span class="material-icons absolute right-3 top-2.5 text-gray-400">search</span>
                        </div>
                    </div>

                    {{-- SELECTS --}}

                    <x-select name="empresa_id" label="Empresa" :options="$empresas" wire:model.live="empresa_id" />
                    <x-select name="rol_id" label="Rol" :options="$roles" wire:model.live="rol_id" />
                    <x-select name="departamento_id" label="Departamento" :options="$departamentos" wire:model.live="departamento_id" />
                    <x-select name="cargo_id" label="Cargo" :options="$cargos" wire:model.live="cargo_id" />
                    <x-select name="ubicacion_id" label="Ubicación" :options="$ubicaciones" wire:model.live="ubicacion_id" />


                    {{-- ACTIONS --}}
                    <button
                        class="ml-auto bg-cerberus-dark border border-cerberus-steel text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        Acciones
                        <span class="material-icons text-sm">expand_more</span>
                    </button>
                </div>

                {{-- FILA 2 --}}
                <div class="flex items-center gap-4 mt-4 text-sm text-cerberus-light">

                    <span class="text-white">Mostrar:</span>

                    <label class="flex items-center gap-1">
                        <input type="radio" value="" wire:model.live="estado">
                        <span>Todos</span>
                    </label>

                    <label class="flex items-center gap-1">
                        <input type="radio" value="Activo" wire:model.live="estado">
                        <span>Activos</span>
                    </label>

                    <label class="flex items-center gap-1">
                        <input type="radio" value="Inactivo" wire:model.live="estado">
                        <span>Inactivos</span>
                    </label>

                    {{-- RESET --}}
                    <button wire:click="resetFilters"
                        class="ml-auto bg-red-600/20 border border-red-700 text-red-300 px-3 py-2 rounded-lg hover:bg-red-700/40">
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </x-slot>
    </x-crud-header>

    {{-- TABLA REUSABLE --}}
    <x-crud-table :headers="['Nombre', 'Username', 'Email', 'Rol', 'Ficha', 'Estado', 'Acciones']" :paginated="$usuarios" export :filters="$this->filterParams">
        @foreach ($usuarios as $u)
            <tr class="hover:bg-cerberus-darkest">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ $u->foto ? asset('storage/'.$u->foto) : 'https://ui-avatars.com/api/?name='.$u->name }}" 
                            class="w-10 h-10 rounded-full">
                        <div class="text-white font-medium">{{ $u->name }}</div>
                    </div>
                </td>

                <td class="px-4 py-3 text-white">{{ $u->username }}</td>
                <td class="px-4 py-3 text-cerberus-light">{{ $u->email }}</td>

                <td class="px-4 py-3">
                    @foreach ($u->roles as $r)
                        <span
                            class="px-2 py-1 text-xs rounded-md bg-blue-800 text-blue-300 mr-1">{{ $r->name }}</span>
                    @endforeach
                </td>

                <td class="px-4 py-3">{{ $u->ficha }}</td>

                <td class="px-4 py-3">
                    @if ($u->estado === 'Activo')
                        <span
                            class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-400 inset-ring inset-ring-green-500/20">Activo</span>
                    @else
                        <span
                            class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-400 inset-ring inset-ring-red-400/20">Inactivo</span>
                    @endif
                </td>

                <td class="px-6 py-4 text-center">

                    <x-table-actions row-id="user-{{ $u->id }}" :viewModalId="'viewUser-' . $u->id" :editUrl="route('admin.usuarios.edit', $u)"
                        deleteModalId="deleteUser-{{ $u->id }}" />

                    <x-view-modal id="viewUser-{{ $u->id }}" title="Detalle del Usuario" :data="$u" />

                    <x-delete-modal id="deleteUser-{{ $u->id }}" title="Eliminar Usuario" :message="'¿Seguro que deseas eliminar a ' . $u->name . '?'"
                        :action="route('admin.usuarios.destroy', $u)" />

                </td>

            </tr>
        @endforeach
    </x-crud-table>

</div>
