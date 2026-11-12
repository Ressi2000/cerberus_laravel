<div>
    @if ($open && $this->componente)
        @php
            $granel   = $this->componente->esAGranel();
            $resumen  = $this->resumen;
            $pestanas = $granel
                ? ['movimientos' => 'Entradas y salidas', 'piezas' => 'Piezas de antes']
                : ['stock' => 'En stock', 'piezas' => 'Todas las piezas', 'movimientos' => 'Entradas y salidas'];
            // A granel solo tiene sentido "Piezas de antes" si tuvo piezas cuando era por unidad.
            if ($granel && array_sum($resumen) === 0) {
                unset($pestanas['piezas']);
            }
            $colorEstado = fn ($estado) => match ($estado) {
                'en_almacen'  => 'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30',
                'instalada'   => 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
                'en_deposito' => 'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30',
                default       => 'bg-gray-100 dark:bg-cerberus-steel/20 text-gray-600 dark:text-cerberus-light border-gray-200 dark:border-cerberus-steel/40',
            };
        @endphp

        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 85vh;">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-cerberus-light">
                            Historial de la variante · {{ $this->componente->familia?->nombre ?? 'Sin familia' }}
                        </p>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent">inventory_2</span>
                            {{ $this->componente->nombre }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-cerberus-light mt-0.5">
                            {{ $this->componente->stock_actual }} {{ $this->componente->unidad }} en stock
                            @unless ($granel)
                                @if (($resumen['instalada'] ?? 0) > 0) · {{ $resumen['instalada'] }} instalada(s) @endif
                                @if (($resumen['en_deposito'] ?? 0) > 0) · {{ $resumen['en_deposito'] }} en depósito @endif
                            @endunless
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                @if (count($pestanas) > 1)
                    <div class="flex gap-1 px-6 pt-3 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0 overflow-x-auto">
                        @foreach ($pestanas as $clave => $etiqueta)
                            <button wire:click="verPestana('{{ $clave }}')"
                                @class([
                                    'px-3 py-2 text-sm border-b-2 -mb-px whitespace-nowrap transition',
                                    'border-cerberus-primary dark:border-cerberus-accent text-cerberus-primary dark:text-cerberus-accent font-medium' => $pestana === $clave,
                                    'border-transparent text-gray-500 dark:text-cerberus-light hover:text-gray-700 dark:hover:text-white' => $pestana !== $clave,
                                ])>
                                {{ $etiqueta }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="px-6 py-4 overflow-y-auto flex-1 space-y-3">

                    {{-- ENTRADAS Y SALIDAS --}}
                    @if ($pestana === 'movimientos')
                        @forelse ($this->usos as $mov)
                            <div wire:key="uso-{{ $mov->id }}"
                                 class="bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50 rounded-lg px-4 py-2.5 flex items-center justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <span @class(['tabular-nums', 'text-green-600 dark:text-green-400' => $mov->tipo === 'Entrada', 'text-red-600 dark:text-red-400' => $mov->tipo === 'Salida'])>
                                            {{ $mov->tipo === 'Entrada' ? '+' : '−' }}{{ $mov->cantidad }}
                                        </span>
                                        @if ($mov->mantenimiento)
                                            · <a href="{{ route('admin.mantenimientos.show', $mov->mantenimiento) }}" wire:navigate
                                                 class="text-cerberus-primary dark:text-cerberus-accent hover:underline">
                                                Caso #{{ $mov->mantenimiento->id }} · {{ $mov->mantenimiento->equipo->codigo_interno ?? '' }}
                                            </a>
                                        @else
                                            · <span class="text-gray-600 dark:text-cerberus-light">{{ $mov->motivo ?? '—' }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-cerberus-light">
                                        {{ $mov->registradoPor?->name ?? 'Sistema' }}
                                        @if ($mov->observaciones) · <span class="italic">{{ $mov->observaciones }}</span> @endif
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-cerberus-steel whitespace-nowrap">{{ $mov->created_at->format('d/m/Y') }}</span>
                            </div>
                        @empty
                            <div class="text-center text-sm text-gray-400 dark:text-cerberus-steel py-6">Todavía no tiene movimientos.</div>
                        @endforelse

                    {{-- TODAS LAS PIEZAS --}}
                    @elseif ($pestana === 'piezas')
                        @forelse ($this->todasLasPiezas as $pieza)
                            <button type="button" wire:key="todas-{{ $pieza->id }}" wire:click="verPieza({{ $pieza->id }})"
                                class="w-full text-left bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50
                                       hover:border-cerberus-primary dark:hover:border-cerberus-accent rounded-lg px-4 py-2.5
                                       flex items-center justify-between gap-3 transition">
                                <div class="min-w-0">
                                    <p class="text-sm font-mono text-gray-900 dark:text-white truncate">{{ $pieza->codigo() }}</p>
                                    <p class="text-xs text-gray-500 dark:text-cerberus-light">
                                        {{ $pieza->ubicacion() }} · {{ $pieza->labelCondicion() }}
                                        @if ($pieza->equipoOrigen) · de {{ $pieza->equipoOrigen->codigo_interno }} @endif
                                    </p>
                                </div>
                                <span class="flex-shrink-0 flex items-center gap-1">
                                    <span class="px-2 py-0.5 text-xs rounded-full border whitespace-nowrap {{ $colorEstado($pieza->estado) }}">{{ $pieza->labelEstado() }}</span>
                                    <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel">chevron_right</span>
                                </span>
                            </button>
                        @empty
                            <div class="text-center text-sm text-gray-400 dark:text-cerberus-steel py-6">Esta variante todavía no tiene piezas.</div>
                        @endforelse
                        @if ($this->todasLasPiezas->count() >= \App\Livewire\Almacen\ComponentePiezasModal::LIMITE)
                            <p class="text-xs text-center text-gray-400 dark:text-cerberus-steel">
                                Se muestran las {{ \App\Livewire\Almacen\ComponentePiezasModal::LIMITE }} más recientes. Busca un serial en Almacén para encontrar una en particular.
                            </p>
                        @endif

                    {{-- EN STOCK --}}
                    @else
                    @forelse ($this->piezas as $pieza)
                        <button type="button" wire:key="stock-{{ $pieza->id }}" wire:click="verPieza({{ $pieza->id }})"
                            class="w-full text-left bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50
                                   hover:border-cerberus-primary dark:hover:border-cerberus-accent
                                   rounded-lg px-4 py-3 flex items-start justify-between gap-3 transition">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                    <span class="font-mono">{{ $pieza->codigo() }}</span>

                                    <span @class([
                                        'px-1.5 py-0.5 text-[10px] rounded-full border font-semibold uppercase tracking-wide',
                                        'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30' => $pieza->condicion === \App\Models\PiezaExtraida::CONDICION_NUEVO,
                                        'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30' => $pieza->condicion === \App\Models\PiezaExtraida::CONDICION_REUTILIZADO,
                                    ])>
                                        {{ $pieza->labelCondicion() }}
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-cerberus-light mt-1">
                                    @if ($pieza->equipoOrigen)
                                        De {{ $pieza->equipoOrigen->codigo_interno }} ·
                                    @elseif ($pieza->condicion !== \App\Models\PiezaExtraida::CONDICION_NUEVO)
                                        De un equipo eliminado ·
                                    @endif
                                    {{ \App\Models\PiezaExtraida::MOTIVOS[$pieza->motivo] ?? $pieza->motivo }}
                                    · {{ $pieza->created_at->format('d/m/Y') }}
                                    · {{ $pieza->extraidoPor?->name ?? 'Sistema' }}
                                </p>
                                @if ($pieza->observaciones)
                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-1 italic">
                                        "{{ $pieza->observaciones }}"
                                    </p>
                                @endif
                            </div>
                            <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel flex-shrink-0">chevron_right</span>
                        </button>
                    @empty
                        <div class="text-center text-sm text-gray-400 dark:text-cerberus-steel py-6">
                            No hay unidades en stock de esta variante.
                        </div>
                    @endforelse
                    @endif
                </div>

                <div class="flex justify-end px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
