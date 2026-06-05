<div class="space-y-6">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Traslados"
        subtitle="Historial de traslados de equipos entre ubicaciones"
        buttonLabel="Nuevo traslado"
        :buttonUrl="route('admin.traslados.create')">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4 space-y-4">

                {{-- Buscador --}}
                <x-form.input
                    label="Buscar"
                    wire:model.live.400ms="busqueda"
                    placeholder="Número, motivo, quien recibe, ubicación..."
                />

                {{-- Filtros secundarios --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                    <x-form.select label="Origen" wire:model.live="filtro_origen">
                        <option value="">Todas las ubicaciones</option>
                        @foreach ($this->ubicaciones as $ub)
                            <option value="{{ $ub->id }}">{{ $ub->nombre }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select label="Destino" wire:model.live="filtro_destino">
                        <option value="">Todas las ubicaciones</option>
                        @foreach ($this->ubicaciones as $ub)
                            <option value="{{ $ub->id }}">{{ $ub->nombre }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.input type="date" label="Fecha desde" wire:model.live="filtro_fecha_desde" />
                    <x-form.input type="date" label="Fecha hasta" wire:model.live="filtro_fecha_hasta" />
                </div>

                @if ($busqueda || $filtro_origen || $filtro_destino || $filtro_fecha_desde || $filtro_fecha_hasta)
                    <div>
                        <button wire:click="limpiarFiltros"
                            class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300
                                   flex items-center gap-1 transition">
                            <span class="material-icons text-xs">close</span>
                            Limpiar filtros
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── Tabla ────────────────────────────────────────────────────────────── --}}
    <x-table.crud-table :paginator="$this->traslados" :total="$this->traslados->total()">

        <x-slot name="head">
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel">
                N°
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel">
                Fecha
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel">
                Origen → Destino
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel">
                Motivo
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel">
                Equipos
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel">
                Recibe / Autoriza
            </th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel">
                Acciones
            </th>
        </x-slot>

        <x-slot name="body">
            @forelse ($this->traslados as $traslado)
                <tr class="hover:bg-gray-50 dark:hover:bg-cerberus-dark/40 transition-colors border-b border-gray-100 dark:border-cerberus-steel/20">

                    {{-- Número --}}
                    <td class="px-4 py-3">
                        <span class="text-sm font-semibold text-cerberus-primary dark:text-cerberus-accent">
                            {{ $traslado->numero }}
                        </span>
                    </td>

                    {{-- Fecha --}}
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ $traslado->fecha_traslado->format('d/m/Y') }}
                        </span>
                    </td>

                    {{-- Origen → Destino --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700/40 text-slate-700 dark:text-slate-300">
                                {{ $traslado->ubicacionOrigen?->nombre ?? '—' }}
                            </span>
                            <span class="material-icons text-sm text-gray-400 dark:text-cerberus-steel">arrow_forward</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
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

                    {{-- Cantidad de equipos --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                     bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                                     text-cerberus-primary dark:text-cerberus-accent">
                            <span class="material-icons text-xs">devices</span>
                            {{ $traslado->items->count() }}
                        </span>
                    </td>

                    {{-- Recibe / Autoriza --}}
                    <td class="px-4 py-3">
                        <div class="text-xs space-y-0.5">
                            <div class="flex items-center gap-1 text-gray-700 dark:text-cerberus-light">
                                <span class="material-icons text-xs text-gray-400">person</span>
                                <span>{{ $traslado->recibe?->name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-gray-500 dark:text-cerberus-steel">
                                <span class="material-icons text-xs text-gray-400">verified_user</span>
                                <span>{{ $traslado->autoriza?->name ?? '—' }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.traslados.show', $traslado) }}"
                                title="Ver detalle"
                                class="w-8 h-8 rounded-lg flex items-center justify-center
                                       text-gray-400 dark:text-cerberus-steel
                                       hover:bg-gray-100 dark:hover:bg-cerberus-steel/20
                                       hover:text-gray-700 dark:hover:text-white transition">
                                <span class="material-icons text-base">visibility</span>
                            </a>
                            <a href="{{ route('admin.traslados.planilla', $traslado) }}"
                                target="_blank"
                                title="Descargar planilla PDF"
                                class="w-8 h-8 rounded-lg flex items-center justify-center
                                       text-gray-400 dark:text-cerberus-steel
                                       hover:bg-red-50 dark:hover:bg-red-900/20
                                       hover:text-red-600 dark:hover:text-red-400 transition">
                                <span class="material-icons text-base">picture_as_pdf</span>
                            </a>
                        </div>
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
        </x-slot>

    </x-table.crud-table>

</div>
