<div class="space-y-6">

    {{-- ── Modal de confirmación de eliminación ───────────────────────────── --}}
    <livewire:traslados.traslado-delete-modal />

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Traslados"
        subtitle="Historial de traslados de equipos entre ubicaciones"
        buttonLabel="Nuevo traslado"
        :buttonUrl="route('admin.traslados.create')">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4 space-y-4">

                <x-form.input
                    label="Buscar"
                    wire:model.live.400ms="busqueda"
                    placeholder="Número, motivo, quien recibe, ubicación..."
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <x-form.select
                        label="Origen"
                        wire:model.live="filtro_origen"
                        :options="$this->ubicacionesOpciones"
                        placeholder="Todas las ubicaciones"
                    />
                    <x-form.select
                        label="Destino"
                        wire:model.live="filtro_destino"
                        :options="$this->ubicacionesOpciones"
                        placeholder="Todas las ubicaciones"
                    />
                    <x-form.input type="date" label="Fecha desde" wire:model.live="filtro_fecha_desde" />
                    <x-form.input type="date" label="Fecha hasta"  wire:model.live="filtro_fecha_hasta" />
                </div>

                @if ($busqueda || $filtro_origen || $filtro_destino || $filtro_fecha_desde || $filtro_fecha_hasta)
                    <button wire:click="limpiarFiltros"
                        class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300
                               flex items-center gap-1 transition">
                        <span class="material-icons text-xs">close</span>
                        Limpiar filtros
                    </button>
                @endif
            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── Tabla ────────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['N°', 'Fecha', 'Origen → Destino', 'Motivo', 'Equipos', 'Recibe / Autoriza', 'Acciones']"
        :paginated="$this->traslados">

        @forelse ($this->traslados as $traslado)
            <tr wire:key="traslado-{{ $traslado->id }}"
                class="hover:bg-gray-50 dark:hover:bg-cerberus-dark/40 transition-colors">

                {{-- Número --}}
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="text-sm font-semibold text-cerberus-primary dark:text-cerberus-accent">
                        {{ $traslado->numero }}
                    </span>
                </td>

                {{-- Fecha --}}
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="text-sm text-gray-900 dark:text-white">
                        {{ $traslado->fecha_traslado->format('d/m/Y') }}
                    </span>
                </td>

                {{-- Origen → Destino --}}
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1 flex-wrap">
                        <span class="text-xs px-2 py-0.5 rounded-full
                                     bg-slate-100 dark:bg-slate-700/40
                                     text-slate-700 dark:text-slate-300 whitespace-nowrap">
                            {{ $traslado->ubicacionOrigen?->nombre ?? '—' }}
                        </span>
                        <span class="material-icons text-sm text-gray-400 dark:text-cerberus-steel flex-shrink-0">
                            arrow_forward
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                                     bg-blue-100 dark:bg-blue-900/30
                                     text-blue-700 dark:text-blue-300 whitespace-nowrap">
                            {{ $traslado->ubicacionDestino?->nombre ?? '—' }}
                        </span>
                    </div>
                </td>

                {{-- Motivo --}}
                <td class="px-4 py-3 max-w-xs">
                    <p class="text-sm text-gray-700 dark:text-cerberus-light line-clamp-2">
                        {{ $traslado->motivo }}
                    </p>
                </td>

                {{-- Equipos --}}
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                 bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                                 text-cerberus-primary dark:text-cerberus-accent">
                        <span class="material-icons text-xs">devices</span>
                        {{ $traslado->items_count }}
                    </span>
                </td>

                {{-- Recibe / Autoriza --}}
                <td class="px-4 py-3">
                    <div class="text-xs space-y-0.5">
                        <div class="flex items-center gap-1 text-gray-700 dark:text-cerberus-light">
                            <span class="material-icons text-xs text-gray-400">person</span>
                            {{ $traslado->recibe?->name ?? '—' }}
                        </div>
                        <div class="flex items-center gap-1 text-gray-500 dark:text-cerberus-steel">
                            <span class="material-icons text-xs text-gray-400">verified_user</span>
                            {{ $traslado->autoriza?->name ?? '—' }}
                        </div>
                    </div>
                </td>

                {{-- Acciones --}}
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <x-table.table-actions
                        :model="$traslado"
                        :policy="$traslado"
                        deleteEvent="eliminarTraslado"
                        deleteLabel="Eliminar traslado"
                        deleteIcon="delete_forever">

                        <x-slot name="acciones">
                            <li>
                                <a href="{{ route('admin.traslados.show', $traslado) }}"
                                    @click="close()"
                                    class="flex items-center gap-3 px-4 py-2.5 w-full
                                           text-gray-600 dark:text-cerberus-light
                                           hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                           hover:text-[#1E40AF] dark:hover:text-white
                                           transition-colors duration-100">
                                    <span class="material-icons text-base text-[#1E40AF]/70 dark:text-cerberus-accent">
                                        visibility
                                    </span>
                                    Ver detalle
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.traslados.planilla', $traslado) }}"
                                    target="_blank"
                                    @click="close()"
                                    class="flex items-center gap-3 px-4 py-2.5 w-full
                                           text-gray-600 dark:text-cerberus-light
                                           hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                           hover:text-red-600 dark:hover:text-red-400
                                           transition-colors duration-100">
                                    <span class="material-icons text-base text-red-500">picture_as_pdf</span>
                                    Descargar planilla
                                </a>
                            </li>
                        </x-slot>

                    </x-table.table-actions>
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-16 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel/50">
                            local_shipping
                        </span>
                        <p class="text-sm text-gray-500 dark:text-cerberus-steel">
                            No se encontraron traslados con los filtros actuales.
                        </p>
                        @can('create', \App\Models\Traslado::class)
                            <a href="{{ route('admin.traslados.create') }}"
                                class="text-sm text-cerberus-primary dark:text-cerberus-accent font-medium hover:underline">
                                Registrar primer traslado
                            </a>
                        @endcan
                    </div>
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>
