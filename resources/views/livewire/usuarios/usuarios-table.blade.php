<div class="space-y-6">

    {{-- Modales Livewire --}}
    <livewire:usuarios.usuario-view-modal />
    <livewire:usuarios.usuario-delete-modal />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Usuarios"
        subtitle="Gestión de usuarios del sistema"
        buttonLabel="Crear usuario"
        :buttonUrl="route('admin.usuarios.create')">

        <x-slot name="filters">
            <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4 space-y-4">

                {{-- Badge de filtros activos --}}
                @if ($this->activeFiltersCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-cerberus-primary/60 text-white">
                            {{ $this->activeFiltersCount }} filtro(s) activo(s)
                        </span>
                        <button wire:click="resetFilters"
                                class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                            <span class="material-icons text-xs">close</span>
                            Limpiar todos
                        </button>
                    </div>
                @endif

                {{-- FILA 1: Búsqueda — ancho completo --}}
                <x-form.input
                    label="Buscar"
                    wire:model.live.500ms="search"
                    placeholder="Nombre, email, usuario, cédula..."
                    hint="Busca por nombre completo, nombre de usuario, correo electrónico o cédula."
                />

                {{-- FILA 2: Selects principales en grid de 3 columnas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4">
                    <x-form.select
                        label="Empresa (nómina)"
                        :options="$empresas"
                        wire:model.live="empresa_id"
                        hint="Filtra por empresa de nómina del usuario."
                    />
                    <x-form.select
                        label="Rol"
                        :options="$roles"
                        wire:model.live="rol_id"
                        hint="Filtra por rol asignado en el sistema."
                    />
                    <x-form.select
                        label="Departamento"
                        :options="$departamentos"
                        wire:model.live="departamento_id"
                    />
                    <x-form.select
                        label="Cargo"
                        :options="$cargos"
                        wire:model.live="cargo_id"
                    />
                    <x-form.select
                        label="Ubicación"
                        :options="$ubicaciones"
                        wire:model.live="ubicacion_id"
                        hint="La ubicación física controla la visibilidad del usuario para los analistas."
                    />
                    <x-form.select
                        label="Jefe directo"
                        :options="$jefesDisponibles"
                        wire:model.live="jefe_id"
                    />
                </div>

                {{-- FILA 3: Fechas + Estado + Foráneo — grid de 4 columnas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4">

                    <x-form.input
                        type="date"
                        label="Creado desde"
                        wire:model.live="fecha_desde"
                    />

                    <x-form.input
                        type="date"
                        label="Creado hasta"
                        wire:model.live="fecha_hasta"
                    />

                    {{-- Estado: ocupa 1 columna, mismo label+altura que los inputs --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                            Estado
                        </label>
                        <div class="flex items-center gap-4 h-[38px] text-sm text-cerberus-light">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="" wire:model.live="estado"
                                       class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Todos
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="Activo" wire:model.live="estado"
                                       class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Activos
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="Inactivo" wire:model.live="estado"
                                       class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Inactivos
                            </label>
                        </div>
                    </div>

                    {{-- Foráneo: misma estructura label+altura --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                            Tipo de ubicación
                        </label>
                        <div class="flex items-center h-[38px]">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-cerberus-light">
                                <input type="checkbox"
                                       wire:model.live="foraneo"
                                       true-value="1"
                                       false-value=""
                                       class="rounded text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Solo foráneos
                            </label>
                        </div>
                    </div>

                </div>

            </div>
        </x-slot>

    </x-crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Nombre', 'Username', 'Email', 'Rol', 'Ficha', 'Ubicación', 'Estado', 'Acciones']"
        :paginated="$usuarios"
        export
        exportRoute="export.usuarios"
        :filters="$this->filterParams">

        @forelse ($usuarios as $u)
            <tr wire:key="usuario-{{ $u->id }}" class="hover:bg-cerberus-darkest">

                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ $u->foto_url }}" alt="{{ $u->name }}"
                             class="w-9 h-9 rounded-full object-cover border border-cerberus-steel">
                        <div>
                            <p class="text-white font-medium text-sm">{{ $u->name }}</p>
                            <p class="text-cerberus-steel text-xs">{{ $u->cedula }}</p>
                        </div>
                    </div>
                </td>

                <td class="px-4 py-3 text-white text-sm">{{ $u->username }}</td>
                <td class="px-4 py-3 text-cerberus-light text-sm">{{ $u->email }}</td>

                <td class="px-4 py-3">
                    @foreach ($u->roles as $r)
                        <span class="px-2 py-0.5 text-xs rounded-md bg-blue-800 text-blue-300 mr-1">
                            {{ $r->name }}
                        </span>
                    @endforeach
                </td>

                <td class="px-4 py-3 text-cerberus-light text-sm">{{ $u->ficha ?? '—' }}</td>

                <td class="px-4 py-3 text-sm">
                    <span class="text-cerberus-light">{{ $u->ubicacion?->nombre ?? '—' }}</span>
                    @if ($u->ubicacion?->es_estado)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-teal-800/40 text-teal-300">
                            Foráneo
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3">
                    @if ($u->estado === 'Activo')
                        <span class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1
                                     text-xs font-medium text-green-400 ring-1 ring-inset ring-green-500/20">
                            Activo
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1
                                     text-xs font-medium text-red-400 ring-1 ring-inset ring-red-400/20">
                            Inactivo
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions
                        :model="$u"
                        :editUrl="route('admin.usuarios.edit', $u)"
                        viewEvent="openUserView"
                        deleteEvent="openUserDelete"
                        deleteLabel="Inactivar"
                        :policy="$u"
                    />
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-4 py-12 text-center text-cerberus-light">
                    <span class="material-icons text-4xl block mb-2 text-cerberus-steel">person_search</span>
                    No se encontraron usuarios con los filtros aplicados.
                </td>
            </tr>
        @endforelse

    </x-crud-table>

</div>