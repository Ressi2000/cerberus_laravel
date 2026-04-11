<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('configuracion.empresas.empresa-view-modal')
    @livewire('configuracion.empresas.empresa-modal')
    @livewire('configuracion.empresas.empresa-delete-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total',      'value' => $this->total,           'icon' => 'domain'],
        ['title' => 'Eliminadas', 'value' => $this->totalEliminadas, 'icon' => 'delete'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Empresas"
        subtitle="Empresas registradas en el sistema"
        buttonLabel="Nueva empresa"
        buttonEvent="openEmpresaCrear">

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

                <div class="grid grid-cols-1 gap-4">
                    <x-form.input
                        label="Buscar"
                        wire:model.live.400ms="search"
                        placeholder="Nombre o RIF..."
                        hint="Filtra por nombre o RIF de la empresa."
                    />
                </div>

            </div>
        </x-slot>

    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Empresa', 'RIF', 'Teléfono', 'Usuarios', 'Equipos', 'Ubicaciones', 'Acciones']"
        :paginated="$empresas">

        @forelse ($empresas as $empresa)
            <tr wire:key="emp-{{ $empresa->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3">
                    <p class="text-[#1E293B] dark:text-white font-medium text-sm">
                        {{ $empresa->nombre }}
                    </p>
                    @if ($empresa->direccion)
                        <p class="text-gray-400 dark:text-cerberus-steel text-xs truncate max-w-xs">
                            {{ $empresa->direccion }}
                        </p>
                    @endif
                </td>

                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm font-mono">
                    {{ $empresa->rif ?? '—' }}
                </td>

                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">
                    {{ $empresa->telefono ?? '—' }}
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $empresa->usuarios_count }}
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $empresa->equipos_count }}
                </td>

                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $empresa->ubicaciones_count }}
                </td>

                <td class="px-4 py-3 text-center">
                    <x-table.table-actions
                        :model="$empresa"
                        viewEvent="openEmpresaVer"
                        editEvent="openEmpresaEditar"
                        deleteEvent="openEmpresaEliminar"
                        deleteLabel="Eliminar"
                    />
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">
                        domain_disabled
                    </span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">
                        No hay empresas registradas.
                    </p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>