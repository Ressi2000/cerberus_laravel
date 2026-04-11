<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('configuracion.empresas.empresa-view-modal')
    @livewire('configuracion.empresas.empresa-modal')
    @livewire('configuracion.empresas.empresa-delete-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total activas',   'value' => $this->total,             'icon' => 'domain'],
        ['title' => 'Con usuarios',    'value' => $this->totalConUsuarios,  'icon' => 'group'],
        ['title' => 'Con equipos',     'value' => $this->totalConEquipos,   'icon' => 'devices'],
        ['title' => 'Inactivas',       'value' => $this->totalInactivas,    'icon' => 'block'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Empresas"
        subtitle="Organizaciones registradas en el sistema"
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

                <x-form.input
                    label="Buscar"
                    wire:model.live.400ms="search"
                    placeholder="Nombre o RIF..."
                    hint="Filtra por nombre o RIF de la empresa."
                />

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
                                         bg-gray-100 dark:bg-cerberus-steel/40 text-gray-500 dark:text-cerberus-light">
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
        :headers="['Empresa', 'Estado', 'RIF', 'Teléfono', 'Usuarios', 'Equipos', 'Ubicaciones', 'Acciones']"
        export
        exportRoute="export.empresas"
        :filters="$this->filterParams"
        :paginated="$empresas">

        @forelse ($empresas as $empresa)
            <tr wire:key="emp-{{ $empresa->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       {{ ! $empresa->activo ? 'opacity-60 bg-gray-50 dark:bg-cerberus-dark/30' : '' }}
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                <td class="px-4 py-3">
                    <p class="text-[#1E293B] dark:text-white font-medium text-sm">{{ $empresa->nombre }}</p>
                    @if ($empresa->direccion)
                        <p class="text-gray-400 dark:text-cerberus-steel text-xs truncate max-w-xs">
                            {{ $empresa->direccion }}
                        </p>
                    @endif
                </td>

                {{-- Badge activo/inactivo --}}
                <td class="px-4 py-3">
                    @if ($empresa->activo)
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

                <td class="px-4 py-3">
                    <div class="flex items-center gap-1">
                        @if ($empresa->activo)
                            <button wire:click="$dispatch('openEmpresaVer', { id: {{ $empresa->id }} })"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-cerberus-light
                                       hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-cerberus-steel/40 transition"
                                title="Ver detalle">
                                <span class="material-icons text-base">visibility</span>
                            </button>
                            <button wire:click="$dispatch('openEmpresaEditar', { id: {{ $empresa->id }} })"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-cerberus-light
                                       hover:text-cerberus-accent hover:bg-cerberus-steel/40 transition"
                                title="Editar">
                                <span class="material-icons text-base">edit</span>
                            </button>
                            <button wire:click="$dispatch('openEmpresaEliminar', { id: {{ $empresa->id }} })"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-cerberus-light
                                       hover:text-yellow-500 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition"
                                title="Desactivar">
                                <span class="material-icons text-base">block</span>
                            </button>
                        @else
                            <button wire:click="$dispatch('reactivarEmpresa', { id: {{ $empresa->id }} })"
                                wire:confirm="¿Reactivar la empresa «{{ $empresa->nombre }}»?"
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
                <td colspan="8" class="px-4 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">domain_disabled</span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">No hay empresas registradas.</p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>
</div>