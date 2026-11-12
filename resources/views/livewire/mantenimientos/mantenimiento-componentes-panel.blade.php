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
        <div wire:key="mant-comp-{{ $item->id }}" class="flex items-center justify-between py-2.5 {{ ! $loop->last ? 'border-b border-gray-100 dark:border-cerberus-steel/30' : '' }}">
            <div>
                <p class="text-sm text-gray-900 dark:text-white">{{ $item->componente->nombre ?? '—' }}</p>
                @if ($item->pieza?->identificador)
                    <p class="text-xs font-mono text-gray-500 dark:text-cerberus-light">
                        {{ str_starts_with($item->pieza->identificador, 'AUTO-') ? 'Código' : 'Serial' }} {{ $item->pieza->identificador }}
                    </p>
                @endif
                <p class="text-xs text-gray-500 dark:text-cerberus-light">Cantidad: {{ $item->cantidad_requerida }}</p>
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
        <p class="text-sm text-gray-400 dark:text-cerberus-steel text-center py-4">No se han pedido componentes.</p>
    @endforelse
</div>
