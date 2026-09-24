<div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="material-icons text-cerberus-accent text-base">inventory_2</span>
            Componentes del almacén
        </h3>
        @if ($this->mantenimiento->estaAbierto())
            <button wire:click="abrirForm"
                class="text-xs font-medium text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1">
                <span class="material-icons text-sm">add</span> Pedir componente
            </button>
        @endif
    </div>

    @if ($formAbierto)
        <div class="bg-gray-50 dark:bg-cerberus-dark/50 border border-gray-200 dark:border-cerberus-steel/50 rounded-lg p-4 mb-4 space-y-3">

            @if (! $solicitarNuevo)
                <div wire:key="select-existente">
                    <x-form.select
                        searchable
                        label="Componente"
                        placeholder="Selecciona del almacén"
                        :options="$this->componentesDisponibles"
                        wire:model="componente_id"
                        :error="$errors->first('componente_id')"
                    />
                </div>

                @can('create', \App\Models\ComponenteAlmacen::class)
                    <button type="button" wire:click="$set('solicitarNuevo', true)"
                        class="text-xs text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1">
                        <span class="material-icons text-sm">add_circle_outline</span>
                        No está en el almacén — solicitar componente nuevo
                    </button>
                @endcan
            @else
                <div class="bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/30 rounded-lg p-3 space-y-3">
                    <p class="text-xs text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                        <span class="material-icons text-sm">info</span>
                        Se dará de alta en el almacén con stock 0 y quedará pedido como "Pendiente".
                    </p>
                    <x-form.input label="Nombre del componente" wire:model="nuevoNombre" placeholder="Ej: Pantalla LCD 15.6&quot;" :error="$errors->first('nuevoNombre')" />
                    <x-form.input label="Unidad" wire:model="nuevaUnidad" placeholder="unidad, par, metro..." :error="$errors->first('nuevaUnidad')" />
                    <button type="button" wire:click="$set('solicitarNuevo', false)" class="text-xs text-gray-500 dark:text-cerberus-light hover:underline">
                        Cancelar, elegir del almacén
                    </button>
                </div>
            @endif

            <x-form.input
                label="Cantidad"
                type="number"
                wire:model="cantidad"
                :error="$errors->first('cantidad')"
            />
            <div class="flex justify-end gap-2">
                <button wire:click="cerrarForm" class="px-3 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="pedir" class="px-3 py-1.5 text-xs rounded-lg bg-[#1E40AF] text-white">
                    {{ $solicitarNuevo ? 'Solicitar' : 'Pedir' }}
                </button>
            </div>
        </div>
    @endif

    @forelse ($this->mantenimiento->componentes as $item)
        <div wire:key="mant-comp-{{ $item->id }}" class="flex items-center justify-between py-2.5 {{ ! $loop->last ? 'border-b border-gray-100 dark:border-cerberus-steel/30' : '' }}">
            <div>
                <p class="text-sm text-gray-900 dark:text-white">{{ $item->componente->nombre ?? '—' }}</p>
                <p class="text-xs text-gray-500 dark:text-cerberus-light">Cantidad: {{ $item->cantidad_requerida }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($item->estado === 'Pendiente')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                 bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-400
                                 border border-purple-200 dark:border-purple-500/30">
                        <span class="material-icons text-xs">hourglass_empty</span> Pendiente
                    </span>
                    @if ($this->mantenimiento->estaAbierto())
                        <button wire:click="entregarPendiente({{ $item->id }})"
                            class="text-xs text-cerberus-primary dark:text-cerberus-accent hover:underline">
                            Entregar ahora
                        </button>
                    @endif
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
