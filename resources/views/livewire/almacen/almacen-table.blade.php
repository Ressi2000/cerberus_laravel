<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('almacen.componente-modal')
    @livewire('almacen.movimiento-stock-modal')
    @livewire('almacen.componente-piezas-modal')
    @livewire('almacen.registrar-entrada-modal')
    @livewire('almacen.pieza-ficha-modal')
    @livewire('almacen.traslado-almacen-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Familias',                   'value' => $this->totalFamilias,         'icon' => 'category'],
        ['title' => 'Piezas con serial en almacén', 'value' => $this->totalPiezasEnAlmacen, 'icon' => 'qr_code_2'],
        ['title' => 'Unidades a granel',           'value' => $this->totalUnidadesAGranel, 'icon' => 'inventory'],
        ['title' => 'Variantes sin stock',         'value' => $this->totalSinStock,        'icon' => 'production_quantity_limits'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Almacén de Componentes"
        subtitle="Stock de piezas y repuestos reutilizables, por empresa"
        buttonLabel="Registrar entrada"
        buttonEvent="openRegistrarEntrada">

        <x-slot name="actions">
            <a href="{{ route('admin.almacen.solicitudes') }}" wire:navigate
               title="Componentes que los casos pidieron y siguen sin entregarse"
               class="relative inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                      bg-white dark:bg-cerberus-mid border border-[#1E40AF] dark:border-cerberus-accent
                      text-[#1E40AF] dark:text-cerberus-accent hover:bg-[#1E40AF]/5 dark:hover:bg-cerberus-primary/10
                      transition-all duration-150 flex-shrink-0">
                <span class="material-icons text-base">pending_actions</span>
                Solicitudes pendientes
                @if ($this->totalSolicitudesPendientes > 0)
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-purple-600 dark:bg-purple-500 text-white">
                        {{ $this->totalSolicitudesPendientes }}
                    </span>
                @endif
            </a>
        </x-slot>

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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input
                        label="Buscar"
                        wire:model.live.400ms="search"
                        placeholder="Nombre, descripción o serial..."
                    />

                    @can('viewAny', \App\Models\ComponenteAlmacen::class)
                        @if (Auth::user()->hasRole('Administrador'))
                            <x-form.select
                                label="Empresa"
                                placeholder="Todas"
                                :options="$this->empresasOpciones"
                                wire:model.live="empresa_id"
                            />
                        @endif
                    @endcan
                </div>

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
                        Mostrar desactivados
                    </span>
                </div>

            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── PIEZAS QUE COINCIDEN CON LA BÚSQUEDA (por serial o código) ──────── --}}
    @if ($this->piezasEncontradas->isNotEmpty())
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-cerberus-light mb-2 flex items-center gap-1.5">
                <span class="material-icons text-sm text-cerberus-accent">qr_code_2</span>
                Piezas con ese serial o código
            </p>
            <div class="space-y-1.5">
                @foreach ($this->piezasEncontradas as $pieza)
                    <button type="button" wire:key="encontrada-{{ $pieza->id }}"
                        wire:click="$dispatch('openPiezaFicha', { piezaId: {{ $pieza->id }} })"
                        class="w-full text-left flex items-center justify-between gap-3 px-3 py-2 rounded-lg
                               bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50
                               hover:border-cerberus-primary dark:hover:border-cerberus-accent transition">
                        <span class="min-w-0">
                            <span class="block text-sm font-mono text-gray-900 dark:text-white truncate">{{ $pieza->codigo() }}</span>
                            <span class="block text-xs text-gray-500 dark:text-cerberus-light truncate">
                                {{ $pieza->componenteAlmacen?->nombre ?? $pieza->descripcion() }} · {{ $pieza->ubicacion() }}
                            </span>
                        </span>
                        <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel">chevron_right</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── FAMILIAS ────────────────────────────────────────────────────────── --}}
    @if ($this->hayComponentesSinFamilia)
        <div class="flex items-start gap-2 px-4 py-3 rounded-xl text-sm
                    bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300
                    border border-amber-200 dark:border-amber-500/30">
            <span class="material-icons text-base">info</span>
            <span>Hay componentes sin familia (al final de la lista). Edítalos para asignarles una y que el almacén quede ordenado.</span>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->grupos as $grupo)
            @php
                $familia = $grupo['familia'];
                $granel  = $familia?->esAGranel();
                $clave   = $familia?->id ?? 'sin';
            @endphp
            <div wire:key="grupo-{{ $clave }}" x-data="{ open: {{ $loop->first || $this->search ? 'true' : 'false' }} }"
                 class="bg-white dark:bg-cerberus-mid border rounded-xl overflow-hidden
                        {{ $familia ? 'border-gray-200 dark:border-cerberus-steel' : 'border-amber-300 dark:border-amber-500/40' }}">

                {{-- Encabezado de la familia --}}
                <button type="button" @click="open = !open" :aria-expanded="open"
                        class="w-full flex flex-wrap items-center gap-3 px-4 py-3 text-left
                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors">
                    <span class="material-icons text-gray-400 dark:text-cerberus-steel transition-transform duration-150"
                          :class="open ? 'rotate-90' : ''">chevron_right</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $familia?->nombre ?? 'Sin familia' }}</span>

                    @if ($familia)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     {{ $granel
                                         ? 'bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-300'
                                         : 'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-300' }}">
                            <span class="material-icons text-xs">{{ $granel ? 'inventory' : 'qr_code_2' }}</span>
                            {{ $granel ? 'A granel' : 'Por unidad' }}
                        </span>
                        @if ($familia->tieneFicha())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                         bg-teal-50 dark:bg-teal-500/15 text-teal-700 dark:text-teal-300"
                                  title="Sus piezas son una característica de los equipos">
                                <span class="material-icons text-xs">tune</span> Ficha técnica
                            </span>
                        @endif
                    @else
                        <span class="text-xs text-amber-700 dark:text-amber-300">Asígnales una familia</span>
                    @endif

                    <span class="ml-auto flex items-center gap-4 text-sm text-gray-500 dark:text-cerberus-accent">
                        <span>{{ $grupo['variantes'] }} variante{{ $grupo['variantes'] === 1 ? '' : 's' }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white tabular-nums">{{ $grupo['stock'] }} en stock</span>
                        @if ($grupo['sin_stock'] > 0)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400">
                                {{ $grupo['sin_stock'] }} sin stock
                            </span>
                        @endif
                    </span>
                </button>

                {{-- Variantes --}}
                <div x-show="open" x-cloak class="border-t border-gray-100 dark:border-cerberus-steel/40">
                    @foreach ($grupo['componentes'] as $componente)
                        <div wire:key="comp-{{ $componente->id }}"
                             class="flex flex-wrap items-center gap-3 pl-12 pr-4 py-2.5 border-b last:border-b-0
                                    border-gray-100 dark:border-cerberus-steel/30
                                    hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors
                                    {{ ! $componente->activo ? 'opacity-60' : '' }}">

                            <div class="flex-1 min-w-[12rem]">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $componente->nombre }}</p>
                                <p class="text-xs text-gray-500 dark:text-cerberus-light">
                                    @if (Auth::user()->hasRole('Administrador')) {{ $componente->empresa->nombre ?? '—' }} · @endif
                                    {{ $componente->unidad }}
                                    @if (! $componente->activo) · <span class="text-gray-400">Desactivado</span> @endif
                                </p>
                            </div>

                            <div class="text-right min-w-[7rem]">
                                <span @class([
                                    'inline-flex items-center px-2 py-0.5 text-xs rounded-full border font-semibold tabular-nums',
                                    'bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/30' => $componente->stock_actual === 0,
                                    'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30' => $componente->stock_actual > 0,
                                ])>
                                    {{ $componente->stock_actual }} {{ $granel ? 'unid.' : ($componente->stock_actual === 1 ? 'pieza' : 'piezas') }}
                                </span>
                                @if (! $granel && ($componente->nuevas_en_stock > 0 || $componente->reutilizadas_en_stock > 0))
                                    <p class="text-[10px] text-gray-400 dark:text-cerberus-steel mt-1 whitespace-nowrap">
                                        {{ collect([
                                            $componente->nuevas_en_stock > 0 ? $componente->nuevas_en_stock . ' nueva(s)' : null,
                                            $componente->reutilizadas_en_stock > 0 ? $componente->reutilizadas_en_stock . ' usada(s)' : null,
                                        ])->filter()->implode(' · ') }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex-shrink-0">
                                <x-table.table-actions :model="$componente" editEvent="openComponenteEditar">
                        <x-slot name="acciones">
                                <li>
                                    <button wire:click="$dispatch('openComponentePiezas', { componenteId: {{ $componente->id }} })"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                   hover:text-cerberus-primary dark:hover:text-cerberus-accent
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-cerberus-accent">history</span>
                                        Ver historial
                                    </button>
                                </li>
                            <li>
                                <button wire:click="$dispatch('openRegistrarEntrada', { componenteId: {{ $componente->id }} })"
                                        @click="close()"
                                        class="flex items-center gap-3 px-4 py-2.5 w-full
                                               text-gray-600 dark:text-cerberus-light
                                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                               hover:text-blue-600 dark:hover:text-cerberus-accent
                                               transition-colors duration-100">
                                    <span class="material-icons text-base text-cerberus-accent">add_box</span>
                                    Registrar entrada
                                </button>
                            </li>
                            @if ($componente->stock_actual > 0)
                                <li>
                                    <button wire:click="$dispatch('openMovimientoStock', { componenteId: {{ $componente->id }} })"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                   hover:text-blue-600 dark:hover:text-cerberus-accent
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-cerberus-accent">remove_circle_outline</span>
                                        Registrar salida (ajuste o dañada)
                                    </button>
                                </li>
                                @if (auth()->user()->hasRole('Administrador'))
                                    <li>
                                        <button wire:click="$dispatch('openTrasladoAlmacen', { componenteId: {{ $componente->id }} })"
                                                @click="close()"
                                                class="flex items-center gap-3 px-4 py-2.5 w-full
                                                       text-gray-600 dark:text-cerberus-light
                                                       hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                       hover:text-blue-600 dark:hover:text-cerberus-accent
                                                       transition-colors duration-100">
                                            <span class="material-icons text-base text-cerberus-accent">swap_horiz</span>
                                            Trasladar a otra empresa
                                        </button>
                                    </li>
                                @endif
                            @endif
                            @if ($componente->activo)
                                <li>
                                    <div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div>
                                </li>
                                <li>
                                    <button wire:click="$dispatch('confirmar', {
                                                titulo: 'Desactivar componente',
                                                mensaje: '¿Desactivar «{{ $componente->nombre }}»? Ya no se podrá usar en nuevos mantenimientos.',
                                                accionEvento: 'componenteDesactivarConfirmado',
                                                accionParams: [{{ $componente->id }}],
                                                confirmLabel: 'Desactivar',
                                            })"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-red-600 dark:text-red-400
                                                   hover:bg-red-50 dark:hover:bg-red-500/10
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base">block</span>
                                        Desactivar
                                    </button>
                                </li>
                            @else
                                <li>
                                    <div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div>
                                </li>
                                <li>
                                    <button wire:click="reactivar({{ $componente->id }})"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                                   text-green-600 dark:text-green-400
                                                   hover:bg-green-50 dark:hover:bg-green-500/10
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base">restart_alt</span>
                                        Reactivar
                                    </button>
                                </li>
                            @endif
                        </x-slot>
                    </x-table.table-actions>
                            </div>
                        </div>
                    @endforeach

                    @if ($familia)
                        <div class="pl-12 pr-4 py-2 bg-gray-50/60 dark:bg-cerberus-dark/30">
                            <button type="button" wire:click="$dispatch('openComponenteCrear', { familiaId: {{ $familia->id }} })"
                                class="text-xs text-cerberus-primary dark:text-cerberus-accent hover:underline inline-flex items-center gap-1">
                                <span class="material-icons text-sm">add</span> Nueva variante de {{ $familia->nombre }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                <span class="material-icons text-4xl block mb-2 opacity-40">inventory_2</span>
                No se encontraron componentes.
            </div>
        @endforelse
    </div>

</div>
