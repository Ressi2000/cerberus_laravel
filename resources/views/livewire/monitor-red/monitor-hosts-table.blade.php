<div class="space-y-6" wire:poll.{{ $intervalo }}s.keep-alive>

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('monitor-red.monitor-host-modal')
    @livewire('monitor-red.importar-hosts-modal')

    @php
        $fmtMs = fn ($v) => $v === null ? '—' : number_format($v, $v < 10 ? 1 : 0) . ' ms';
        $d24   = fn ($h) => isset($disp[$h->id]['d1']) && $disp[$h->id]['d1'] !== null ? number_format($disp[$h->id]['d1'], 2) . ' %' : '—';
        $idsPagina = $hosts->pluck('id')->all();
        $paginaMarcada = $idsPagina && ! array_diff($idsPagina, $seleccion);
        $btn2 = 'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                 bg-white dark:bg-cerberus-mid border border-[#1E40AF] dark:border-cerberus-accent
                 text-[#1E40AF] dark:text-cerberus-accent hover:bg-[#1E40AF]/5 dark:hover:bg-cerberus-primary/10
                 transition-all duration-150 flex-shrink-0 disabled:opacity-60';
    @endphp

    {{-- ── STATS ───────────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Hosts',    'value' => $this->stats['total'],    'icon' => 'lan'],
        ['title' => 'Activos',  'value' => $this->stats['activos'],  'icon' => 'check_circle'],
        ['title' => 'Caídos',   'value' => $this->stats['caidos'],   'icon' => 'wifi_off'],
        ['title' => 'Pausados', 'value' => $this->stats['pausados'], 'icon' => 'pause_circle'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Hosts monitoreados"
        subtitle="Ping cada minuto, con puerto TCP de respaldo para equipos que bloquean ICMP"
        :buttonLabel="auth()->user()->can('create', \App\Models\MonitorHost::class) ? 'Agregar host' : null"
        buttonEvent="openMonitorHostCrear">

        <x-slot name="actions">
            <button wire:click="$dispatch('openImportarHosts')" class="{{ $btn2 }}">
                <span class="material-icons text-base">playlist_add</span>
                Importar varios
            </button>
            <button wire:click="revisarAhora" wire:loading.attr="disabled" wire:target="revisarAhora" class="{{ $btn2 }}">
                <span class="material-icons text-base" wire:loading.class="animate-spin" wire:target="revisarAhora">refresh</span>
                Revisar ahora
            </button>
        </x-slot>

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">

                @if ($this->activeFiltersCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-cerberus-primary/60 text-white">
                            {{ $this->activeFiltersCount }} filtro(s) activo(s)
                        </span>
                        <button wire:click="resetFilters" class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                            <span class="material-icons text-xs">close</span> Limpiar
                        </button>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 {{ $this->empresasOpciones->isNotEmpty() ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4">
                    <x-form.input label="Buscar" wire:model.live.500ms="search" placeholder="Nombre, IP, tipo o proveedor..." />

                    <x-form.select label="Estado" placeholder="Todos" wire:model.live="estado"
                        :options="['Caido' => 'Caídos', 'Activo' => 'Activos', 'Pendiente' => 'Pendientes', 'Pausado' => 'Pausados']" />

                    <x-form.select label="Tipo" placeholder="Todos" wire:model.live="tipo" :options="$this->tiposOpciones" />

                    <x-form.select label="Proveedor / enlace" placeholder="Todos" wire:model.live="proveedor" :options="$this->proveedoresOpciones" />

                    @if ($this->empresasOpciones->isNotEmpty())
                        <x-form.select label="Empresa" placeholder="Todas" wire:model.live="empresa_id" :options="$this->empresasOpciones" />
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <div class="inline-flex rounded-lg border border-gray-200 dark:border-cerberus-steel overflow-hidden">
                        @foreach (['lista' => 'view_list', 'cuadros' => 'grid_view'] as $v => $icono)
                            <button wire:click="$set('vista', '{{ $v }}')"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm
                                           {{ $vista === $v ? 'bg-[#1E40AF] text-white dark:bg-cerberus-primary' : 'bg-white dark:bg-cerberus-mid text-gray-600 dark:text-cerberus-light' }}">
                                <span class="material-icons text-base">{{ $icono }}</span> {{ ucfirst($v) }}
                            </button>
                        @endforeach
                    </div>

                    @if ($vista === 'cuadros')
                        <select wire:model.live="orden" class="text-sm rounded-lg border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white">
                            @foreach (['estado' => 'Ordenar por estado', 'nombre' => 'Por nombre', 'host' => 'Por IP', 'tipo' => 'Por tipo', 'proveedor' => 'Por proveedor', 'latencia_ms' => 'Por latencia'] as $k => $t)
                                <option value="{{ $k }}">{{ $t }}</option>
                            @endforeach
                        </select>
                        <button wire:click="$set('dir', '{{ $dir === 'asc' ? 'desc' : 'asc' }}')"
                                class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-600 dark:text-cerberus-light">
                            {{ $dir === 'asc' ? '↑ Asc' : '↓ Desc' }}
                        </button>
                    @endif

                    @if (count($seleccion))
                        <button wire:click="$dispatch('confirmar', {
                                    titulo: 'Quitar hosts del monitoreo',
                                    mensaje: '¿Quitar {{ count($seleccion) }} host(s) del monitoreo? Su historial de caídas se conserva.',
                                    accionEvento: 'monitorHostEliminarConfirmado',
                                    accionParams: [{{ json_encode(array_values($seleccion)) }}],
                                    confirmLabel: 'Quitar',
                                })"
                                class="sm:ml-auto inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg
                                       text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 hover:bg-red-50 dark:hover:bg-red-500/10">
                            <span class="material-icons text-base">delete</span>
                            Quitar seleccionados ({{ count($seleccion) }})
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    @if ($vista === 'lista')
        {{-- ── LISTA ───────────────────────────────────────────────────────── --}}
        @php
            $cols = [['nombre', 'Host'], ['host', 'IP / DNS'], ['tipo', 'Tipo'], ['proveedor', 'Proveedor'], ['estado', 'Estado'],
                     [null, 'Desde'], ['latencia_ms', 'Latencia'], [null, 'Disp. 24 h'], [null, 'Acciones']];
        @endphp
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel/60 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-cerberus-dark/40 text-xs uppercase text-gray-500 dark:text-cerberus-accent">
                        <tr>
                            <th class="px-4 py-3 w-8">
                                <input type="checkbox" class="rounded border-gray-300 dark:border-cerberus-steel"
                                       @checked($paginaMarcada)
                                       wire:click="seleccionarPagina($event.target.checked, {{ json_encode($idsPagina) }})">
                            </th>
                            @foreach ($cols as [$k, $t])
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                                    @if ($k)
                                        <button wire:click="ordenar('{{ $k }}')" class="inline-flex items-center gap-1 uppercase hover:text-[#1E40AF] dark:hover:text-white">
                                            {{ $t }}
                                            @if ($orden === $k)
                                                <span class="material-icons text-sm">{{ $dir === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                            @endif
                                        </button>
                                    @else
                                        {{ $t }}
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($hosts as $h)
                            <tr wire:key="host-{{ $h->id }}"
                                class="border-b border-gray-100 dark:border-cerberus-steel/30 hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors
                                       {{ $h->estaCaido() ? 'bg-red-50/60 dark:bg-red-500/5' : '' }} {{ $h->pausado ? 'opacity-60' : '' }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" value="{{ $h->id }}" wire:model.live="seleccion"
                                           class="rounded border-gray-300 dark:border-cerberus-steel">
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-gray-900 dark:text-white font-medium">{{ $h->nombre }}</p>
                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                                        @if ($this->empresasOpciones->isNotEmpty()){{ $h->empresa?->nombre }}@endif
                                        @if ($h->equipo) · <a href="{{ route('admin.equipos.show', $h->equipo_id) }}" class="hover:underline">{{ $h->equipo->codigo_interno }}</a>@endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-cerberus-light whitespace-nowrap">
                                    {{ $h->host }}@if ($h->puerto_tcp)<span class="text-xs text-gray-400 dark:text-cerberus-steel">:{{ $h->puerto_tcp }}</span>@endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-cerberus-light">{{ $h->tipo }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-cerberus-light">{{ $h->proveedor ?: '—' }}</td>
                                <td class="px-4 py-3">@include('livewire.monitor-red._estado', ['h' => $h])</td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-cerberus-light whitespace-nowrap" title="Última revisión: {{ $h->ultima_revision?->format('d/m/Y H:i:s') ?? '—' }}">
                                    {{ $h->estado_desde?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-cerberus-light whitespace-nowrap">
                                    {{ $h->estaActivo() ? $fmtMs($h->latencia_ms) : '—' }}
                                    @if ($h->estaActivo() && $h->metodo && $h->metodo !== 'ping')
                                        <span class="text-[10px] text-gray-400 dark:text-cerberus-steel">{{ $h->metodo }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-cerberus-light whitespace-nowrap">{{ $d24($h) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @include('livewire.monitor-red._acciones', ['h' => $h])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                                    <span class="material-icons text-4xl block mb-2 opacity-40">lan</span>
                                    {{ $this->activeFiltersCount ? 'Sin resultados para los filtros actuales.' : 'Aún no hay hosts. Usa «Agregar host» o «Importar varios».' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($hosts->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-cerberus-steel/30">{{ $hosts->links() }}</div>
            @endif
        </div>
    @else
        {{-- ── CUADROS ─────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            @forelse ($hosts as $h)
                <div wire:key="card-{{ $h->id }}"
                     @class([
                        'bg-white dark:bg-cerberus-mid rounded-xl p-4 shadow-sm border border-gray-200 dark:border-cerberus-steel/60 border-l-4',
                        'border-l-green-500' => $h->estaActivo(),
                        'border-l-red-500'   => $h->estaCaido(),
                        'border-l-amber-400' => $h->pausado,
                        'border-l-gray-300 dark:border-l-cerberus-steel' => ! $h->pausado && $h->estado === \App\Models\MonitorHost::ESTADO_PENDIENTE,
                        'opacity-70' => $h->pausado,
                     ])>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate" title="{{ $h->nombre }}">{{ $h->nombre }}</p>
                            <p class="text-xs font-mono text-gray-500 dark:text-cerberus-light">{{ $h->host }}{{ $h->puerto_tcp ? ':' . $h->puerto_tcp : '' }}</p>
                        </div>
                        <input type="checkbox" value="{{ $h->id }}" wire:model.live="seleccion" class="mt-1 rounded border-gray-300 dark:border-cerberus-steel">
                    </div>
                    <div class="flex items-center justify-between mt-3">
                        @include('livewire.monitor-red._estado', ['h' => $h])
                        <span class="text-[11px] px-2 py-0.5 rounded-md bg-gray-100 dark:bg-cerberus-steel/30 text-gray-500 dark:text-cerberus-light">{{ $h->tipo }}</span>
                    </div>
                    <dl class="mt-3 space-y-1 text-xs">
                        <div class="flex justify-between"><dt class="text-gray-400 dark:text-cerberus-steel">Latencia</dt><dd class="font-semibold text-gray-700 dark:text-white">{{ $h->estaActivo() ? $fmtMs($h->latencia_ms) : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-400 dark:text-cerberus-steel">Disp. 24 h</dt><dd class="font-semibold text-gray-700 dark:text-white">{{ $d24($h) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-400 dark:text-cerberus-steel">Proveedor</dt><dd class="text-gray-600 dark:text-cerberus-light">{{ $h->proveedor ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-400 dark:text-cerberus-steel">Desde</dt><dd class="text-gray-600 dark:text-cerberus-light">{{ $h->estado_desde?->format('d/m H:i') ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-2 text-[#1E40AF] dark:text-cerberus-accent">
                        {!! \App\Services\MonitorRed\EstadisticasMonitor::sparkline($series[$h->id] ?? null) !!}
                    </div>
                    <div class="mt-2 flex justify-end">
                        @include('livewire.monitor-red._acciones', ['h' => $h])
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel/60 rounded-xl py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-4xl block mb-2 opacity-40">lan</span>
                    {{ $this->activeFiltersCount ? 'Sin resultados para los filtros actuales.' : 'Aún no hay hosts. Usa «Agregar host» o «Importar varios».' }}
                </div>
            @endforelse
        </div>
        @if ($hosts->hasPages())
            <div>{{ $hosts->links() }}</div>
        @endif
    @endif
</div>
