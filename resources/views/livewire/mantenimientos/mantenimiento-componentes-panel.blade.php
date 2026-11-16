<div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="material-icons text-cerberus-accent text-base">inventory_2</span>
            Usado del almacén
        </h3>
        @if ($this->mantenimiento->permiteRegistrarTrabajo())
            <button wire:click="abrirUsar"
                class="text-xs font-medium text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1">
                <span class="material-icons text-sm">add</span> Usar del almacén
            </button>
        @endif
    </div>

    @forelse ($this->mantenimiento->componentes as $item)
        @php
            // Se entregó en este caso, pero después se sacó del equipo.
            $yaNoEsta = $item->pieza
                && ! ($item->pieza->estado === \App\Models\PiezaExtraida::ESTADO_INSTALADA
                      && (int) $item->pieza->equipo_destino_id === (int) $this->mantenimiento->equipo_id);
        @endphp
        <div wire:key="mant-comp-{{ $item->id }}" class="flex items-center justify-between gap-3 py-2.5 {{ ! $loop->last ? 'border-b border-gray-100 dark:border-cerberus-steel/30' : '' }}">
            <div class="min-w-0">
                <p @class(['text-sm', 'text-gray-900 dark:text-white' => ! $yaNoEsta, 'text-gray-500 dark:text-cerberus-light line-through' => $yaNoEsta])>{{ $item->componente->nombre ?? '—' }}</p>
                @if ($item->pieza)
                    <button type="button" wire:click="$dispatch('openPiezaFicha', { piezaId: {{ $item->pieza->id }} })"
                        class="text-xs font-mono text-cerberus-primary dark:text-cerberus-accent hover:underline" title="Ver la ficha y el recorrido de la pieza">
                        {{ $item->pieza->codigo() }}
                    </button>
                @endif
                @php $devuelto = $this->devueltos[$item->id] ?? 0; @endphp
                <p class="text-xs text-gray-500 dark:text-cerberus-light">
                    Cantidad: {{ $item->cantidad_requerida }}
                    @if ($devuelto > 0)
                        · <span class="text-amber-700 dark:text-amber-300">{{ $devuelto }} devuelta(s) al almacén</span>
                    @endif
                </p>
                @if ($item->componente?->esAGranel() && $item->estado === \App\Models\MantenimientoComponente::ESTADO_ENTREGADO)
                    @php $puestas = $this->granelPuesto[$item->componente_id] ?? 0; @endphp
                    @if ($puestas < $item->cantidad_requerida - $devuelto)
                        <p class="text-xs text-amber-700 dark:text-amber-300">Hoy quedan {{ $puestas }} puesta(s) en el equipo (el resto se retiró después).</p>
                    @endif
                @endif
                @if ($devolverItemId === $item->id)
                    <div class="mt-2 p-3 rounded-lg border border-gray-200 dark:border-cerberus-steel/50 bg-gray-50 dark:bg-cerberus-dark/50 space-y-2 max-w-md">
                        <x-form.input label="¿Cuántas vuelven al almacén?" type="number" wire:model="devolverCantidad" :error="$errors->first('devolverCantidad')" />
                        <x-form.textarea label="Motivo" wire:model="devolverMotivo" rows="2" placeholder="Ej: se registraron 4 y solo se usaron 2" :error="$errors->first('devolverMotivo')" />
                        <div class="flex justify-end gap-2">
                            <button wire:click="cancelarDevolver" class="px-3 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">Cancelar</button>
                            <button wire:click="devolver" class="px-3 py-1.5 text-xs rounded-lg bg-[#1E40AF] text-white">Devolver</button>
                        </div>
                    </div>
                @endif
                @if ($yaNoEsta)
                    <p class="text-xs text-amber-700 dark:text-amber-300">Se retiró del equipo después · hoy: {{ $item->pieza->ubicacion() }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($item->estado === 'Pendiente')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                 bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-400
                                 border border-purple-200 dark:border-purple-500/30">
                        <span class="material-icons text-xs">hourglass_empty</span> Pendiente
                    </span>
                    @if ($this->mantenimiento->permiteRegistrarTrabajo())
                        <button wire:click="entregarPendiente({{ $item->id }})"
                            class="text-xs text-cerberus-primary dark:text-cerberus-accent hover:underline">
                            Entregar ahora
                        </button>
                    @endif
                @elseif ($item->estado === \App\Models\MantenimientoComponente::ESTADO_CANCELADO)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                 bg-gray-100 dark:bg-cerberus-steel/20 text-gray-600 dark:text-cerberus-light
                                 border border-gray-200 dark:border-cerberus-steel/40"
                          title="El caso terminó antes de que hubiera stock">
                        <span class="material-icons text-xs">block</span> Cancelado
                    </span>
                @elseif ($item->componente?->esAGranel() && $item->estado === \App\Models\MantenimientoComponente::ESTADO_ENTREGADO
                         && $this->mantenimiento->permiteRegistrarTrabajo()
                         && min($item->cantidad_requerida - ($this->devueltos[$item->id] ?? 0), $this->granelPuesto[$item->componente_id] ?? 0) > 0
                         && $devolverItemId !== $item->id)
                    <button wire:click="abrirDevolver({{ $item->id }})" class="text-xs text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1"
                            title="Se registró de más: vuelve al almacén">
                        <span class="material-icons text-sm">undo</span> Devolver
                    </button>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                 bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                 border border-green-200 dark:border-green-500/30">
                        <span class="material-icons text-xs">check_circle</span> Entregado
                    </span>
                @elseif ($yaNoEsta)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full whitespace-nowrap
                                 bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400
                                 border border-amber-200 dark:border-amber-500/30">
                        <span class="material-icons text-xs">undo</span> Retirada
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                 bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                 border border-green-200 dark:border-green-500/30">
                        <span class="material-icons text-xs">check_circle</span> Entregado
                    </span>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400 dark:text-cerberus-steel text-center py-4">Todavía no se ha usado nada del almacén en este caso.</p>
    @endforelse
</div>
