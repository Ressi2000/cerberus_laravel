<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('configuracion.licencias-microsoft.licencia-microsoft-view-modal')
    @livewire('configuracion.licencias-microsoft.licencia-microsoft-modal')
    @livewire('configuracion.licencias-microsoft.licencia-microsoft-delete-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total activos',   'value' => $this->total,           'icon' => 'sell'],
        ['title' => 'Con usuarios',    'value' => $this->conUsuarios,     'icon' => 'badge'],
        ['title' => 'Sin usuarios',    'value' => $this->sinUsuarios,     'icon' => 'person_off'],
        ['title' => 'Inactivos',       'value' => $this->totalInactivos,  'icon' => 'block'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Tipos de Licencia Microsoft"
        subtitle="Catálogo de licencias de Microsoft que pueden asignarse a los usuarios"
        buttonLabel="Nuevo tipo de licencia"
        buttonEvent="openLicenciaMicrosoftCrear">

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
                    placeholder="Nombre del tipo de licencia..."
                    hint="Filtra por nombre del tipo de licencia."
                />

                {{-- Toggle: mostrar inactivos --}}
                <div class="flex items-center gap-3 pt-1">
                    <button
                        wire:click="$toggle('mostrar_inactivos')"
                        role="switch"
                        aria-checked="{{ $mostrar_inactivos ? 'true' : 'false' }}"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                               border-2 border-transparent transition-colors duration-200
                               {{ $mostrar_inactivos ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                     shadow ring-0 transition duration-200
                                     {{ $mostrar_inactivos ? 'translate-x-4' : 'translate-x-0' }}">
                        </span>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">
                        Mostrar inactivos
                        @if ($this->totalInactivos > 0)
                            <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs
                                         bg-gray-100 dark:bg-cerberus-steel/40 text-gray-500 dark:text-cerberus-light">
                                {{ $this->totalInactivos }}
                            </span>
                        @endif
                    </span>
                </div>

            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Nombre', 'Estado', 'Usuarios asignados', 'Acciones']"
        export
        exportRoute="export.licencias-microsoft"
        :filters="$this->filterParams"
        :paginated="$tipos">

        @forelse ($tipos as $tipo)
            <tr wire:key="lic-{{ $tipo->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       {{ ! $tipo->activo ? 'opacity-60 bg-gray-50 dark:bg-cerberus-dark/30' : '' }}
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">

                {{-- Nombre --}}
                <td class="px-4 py-3 text-[#1E293B] dark:text-white font-medium text-sm">
                    {{ $tipo->nombre }}
                </td>

                {{-- Badge activo/inactivo --}}
                <td class="px-4 py-3">
                    @if ($tipo->activo)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            <span class="material-icons text-xs">block</span> Inactivo
                        </span>
                    @endif
                </td>

                {{-- Usuarios --}}
                <td class="px-4 py-3 text-center">
                    @if ($tipo->usuarios_count > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-blue-50 dark:bg-cerberus-primary/20 text-blue-700 dark:text-cerberus-accent
                                     border border-blue-200 dark:border-cerberus-primary/30">
                            <span class="material-icons text-xs">badge</span>
                            {{ $tipo->usuarios_count }}
                        </span>
                    @else
                        <span class="text-gray-400 dark:text-cerberus-steel text-sm">—</span>
                    @endif
                </td>

                {{-- Acciones --}}
                <td class="px-4 py-3 text-center">
                    @if ($tipo->activo)
                        <x-table.table-actions
                            :model="$tipo"
                            viewEvent="openLicenciaMicrosoftVer"
                            editEvent="openLicenciaMicrosoftEditar"
                            deleteEvent="openLicenciaMicrosoftEliminar"
                            deleteLabel="Desactivar"
                            deleteIcon="block" />
                    @else
                        <x-table.table-actions :model="$tipo">
                            <x-slot name="acciones">
                                <li>
                                    <button wire:click="$dispatch('reactivarLicenciaMicrosoft', { id: {{ $tipo->id }} })"
                                            wire:confirm="¿Reactivar el tipo de licencia «{{ $tipo->nombre }}»?"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                   hover:text-green-600 dark:hover:text-green-400
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-green-500">restart_alt</span>
                                        Reactivar
                                    </button>
                                </li>
                            </x-slot>
                        </x-table.table-actions>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">badge</span>
                    <p class="text-gray-500 dark:text-cerberus-steel text-sm">No hay tipos de licencia registrados.</p>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>
