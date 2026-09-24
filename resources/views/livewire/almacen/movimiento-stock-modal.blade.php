<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">swap_vert</span>
                        Movimiento de stock
                        @if ($this->componente)
                            <span class="text-gray-400 dark:text-cerberus-steel font-normal text-sm">— {{ $this->componente->nombre }}</span>
                        @endif
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    @if ($this->componente)
                        <div class="bg-gray-50 dark:bg-cerberus-dark/50 border border-gray-200 dark:border-cerberus-steel/50 rounded-lg px-4 py-3 text-sm">
                            Stock actual: <span class="font-semibold text-gray-900 dark:text-white">{{ $this->componente->stock_actual }} {{ $this->componente->unidad }}</span>
                        </div>
                    @endif

                    <x-form.select
                        label="Tipo de movimiento"
                        :options="['Entrada' => 'Entrada (suma al stock)', 'Salida' => 'Salida (resta del stock)']"
                        wire:model="tipo"
                        :error="$errors->first('tipo')"
                    />

                    <x-form.input
                        label="Cantidad"
                        type="number"
                        wire:model="cantidad"
                        placeholder="Ej: 10"
                        :error="$errors->first('cantidad')"
                        required
                    />

                    <x-form.input
                        label="Motivo"
                        wire:model="motivo"
                        placeholder="Ej: Compra, ajuste de inventario..."
                        :error="$errors->first('motivo')"
                    />

                    <x-form.textarea
                        label="Observaciones"
                        wire:model="observaciones"
                        rows="2"
                        placeholder="Detalle opcional..."
                    />

                    @if ($this->componente && $this->componente->movimientos->isNotEmpty())
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-2">Últimos movimientos</p>
                            <div class="max-h-40 overflow-y-auto space-y-1.5">
                                @foreach ($this->componente->movimientos->take(5) as $mov)
                                    <div wire:key="mov-{{ $mov->id }}" class="flex items-center justify-between text-xs bg-gray-50 dark:bg-cerberus-dark/40 rounded-lg px-3 py-2">
                                        <span @class([
                                            'font-medium',
                                            'text-green-600 dark:text-green-400' => $mov->tipo === 'Entrada',
                                            'text-red-600 dark:text-red-400'     => $mov->tipo === 'Salida',
                                        ])>
                                            {{ $mov->tipo === 'Entrada' ? '+' : '-' }}{{ $mov->cantidad }}
                                        </span>
                                        <span class="text-gray-500 dark:text-cerberus-light truncate mx-2">{{ $mov->motivo ?? '—' }}</span>
                                        <span class="text-gray-400 dark:text-cerberus-steel whitespace-nowrap">{{ $mov->created_at->format('d/m/Y') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="guardar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A]
                               text-white transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="guardar" class="material-icons text-sm">save</span>
                        <span wire:loading wire:target="guardar" class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="guardar">Registrar</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
