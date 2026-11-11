<div>
    @if ($open && $this->componente)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 85vh;">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent">inventory_2</span>
                            {{ $this->componente->nombre }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-cerberus-light mt-0.5">
                            @if ($this->componente->esAGranel())
                                {{ $this->componente->stock_actual }} {{ $this->componente->unidad }} en stock — a granel: dónde se usó
                            @else
                                {{ $this->piezas->count() }} pieza(s) en stock — serial y de dónde salió cada una
                            @endif
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-4 overflow-y-auto flex-1 space-y-3">
                    @if ($this->componente->esAGranel())
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
                                    <p class="text-xs text-gray-500 dark:text-cerberus-light">{{ $mov->registradoPor?->name ?? 'Sistema' }}</p>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-cerberus-steel whitespace-nowrap">{{ $mov->created_at->format('d/m/Y') }}</span>
                            </div>
                        @empty
                            <div class="text-center text-sm text-gray-400 dark:text-cerberus-steel py-6">Todavía no tiene movimientos.</div>
                        @endforelse
                    @else
                    @forelse ($this->piezas as $pieza)
                        <div class="bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50
                                    rounded-lg px-4 py-3 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                    <span class="material-icons text-sm text-cerberus-accent">devices</span>
                                    @if ($pieza->equipoOrigen)
                                        <a href="{{ route('admin.equipos.show', $pieza->equipoOrigen) }}"
                                           wire:navigate
                                           class="hover:underline hover:text-cerberus-primary dark:hover:text-cerberus-accent">
                                            {{ $pieza->equipoOrigen->codigo_interno }}
                                        </a>
                                    @elseif ($pieza->condicion === \App\Models\PiezaExtraida::CONDICION_NUEVO)
                                        <span class="text-gray-400 dark:text-cerberus-steel">Sin equipo de origen</span>
                                    @else
                                        <span class="text-gray-400 dark:text-cerberus-steel">Equipo eliminado</span>
                                    @endif

                                    <span @class([
                                        'px-1.5 py-0.5 text-[10px] rounded-full border font-semibold uppercase tracking-wide',
                                        'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30' => $pieza->condicion === \App\Models\PiezaExtraida::CONDICION_NUEVO,
                                        'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30' => $pieza->condicion === \App\Models\PiezaExtraida::CONDICION_REUTILIZADO,
                                    ])>
                                        {{ $pieza->labelCondicion() }}
                                    </span>

                                    @if ($pieza->identificador)
                                        <span class="font-mono text-[11px] text-gray-500 dark:text-cerberus-light" title="Serial">
                                            {{ str_starts_with($pieza->identificador, 'AUTO-') ? 'Código ' : 'Serial ' }}{{ $pieza->identificador }}
                                        </span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-cerberus-light mt-1">
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
                        </div>
                    @empty
                        <div class="text-center text-sm text-gray-400 dark:text-cerberus-steel py-6">
                            No hay unidades en stock para este componente.
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
