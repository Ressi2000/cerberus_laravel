<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('configuracion.departamentos.departamento-view-modal')
    @livewire('configuracion.departamentos.departamento-modal')
    @livewire('configuracion.departamentos.departamento-delete-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total',       'value' => $this->total,           'icon' => 'corporate_fare'],
        ['title' => 'Globales',    'value' => $this->totalGlobales,   'icon' => 'public'],
        ['title' => 'Por empresa', 'value' => $this->totalPorEmpresa, 'icon' => 'business'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Departamentos"
        subtitle="Áreas organizacionales del sistema"
        buttonLabel="Nuevo departamento"
        buttonEvent="openDepartamentoCrear">

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
                        hint="Filtra por nombre o descripción del departamento."
                    />
                    <x-form.select
                        label="Empresa"
                        :options="$empresas"
                        wire:model.live="empresa_id"
                    />
                    <x-form.select
                        label="Tipo"
                        :options="['' => 'Todos', 'global' => 'Globales', 'empresa' => 'Por empresa']"
                        wire:model.live="tipo"
                    />
                </div>

            </div>
        </x-slot>

    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Nombre', 'Descripción', 'Empresa', 'Cargos', 'Usuarios', 'Acciones']"
        :paginated="$departamentos">

        @forelse ($departamentos as $departamento)
            <tr wire:key="dpto-{{ $departamento->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3 text-[#1E293B] dark:text-white font-medium text-sm">
                    {{ $departamento->nombre }}
                </td>

                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm max-w-xs truncate">
                    {{ $departamento->descripcion ?? '—' }}
                </td>

                <td class="px-4 py-3">
                    @if ($departamento->empresa)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-[#1E40AF]/10 dark:bg-cerberus-primary/15
                                     text-[#1E40AF] dark:text-cerberus-accent
                                     border border-[#1E40AF]/20 dark:border-cerberus-primary/30">
                            {{ $departamento->empresa->nombre }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15
                                     text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">public</span> Global
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $departamento->cargos_count }}
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $departamento->usuarios_count }}
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions
                        :model="$departamento"
                        viewEvent="openDepartamentoVer"
                        editEvent="openDepartamentoEditar"
                        deleteEvent="openDepartamentoEliminar"
                        deleteLabel="Eliminar"
                    />
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">
                        corporate_fare
                    </span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">
                        No hay departamentos registrados.
                    </p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>