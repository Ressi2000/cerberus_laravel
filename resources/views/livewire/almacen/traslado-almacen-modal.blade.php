<div>
    @if ($open && $this->componente)
        @php
            $c       = $this->componente;
            $granel  = $c->esAGranel();
            $sinId   = $granel ? 0 : $c->unidadesSinIdentidad();
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 90vh;">

                <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent">swap_horiz</span>
                            Trasladar a otra empresa
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-cerberus-light mt-0.5">
                            {{ $c->nombre }} · almacén de <b>{{ $c->empresa->nombre ?? '—' }}</b> ({{ $c->stock_actual }} en stock)
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition flex-shrink-0">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                    <x-form.select label="Va al almacén de" placeholder="Elige la empresa…" :options="$this->empresas"
                        wire:model="empresaDestinoId" :error="$errors->first('empresaDestinoId')" required />

                    @if ($granel)
                        <x-form.input label="Cantidad" type="number" wire:model="sinSerial" :error="$errors->first('sinSerial')" required />
                    @else
                        @if ($this->piezas->isNotEmpty())
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">¿Cuáles van?</p>
                                <div class="max-h-56 overflow-y-auto space-y-1 rounded-lg border border-gray-200 dark:border-cerberus-steel/50 p-1.5">
                                    @foreach ($this->piezas as $pieza)
                                        <label wire:key="tr-{{ $pieza->id }}"
                                            class="flex items-center gap-2 text-sm rounded-md px-2 py-1.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-cerberus-steel/10">
                                            <input type="checkbox" value="{{ $pieza->id }}" wire:model="piezaIds" class="rounded text-cerberus-primary">
                                            <span class="font-mono text-gray-900 dark:text-white">{{ $pieza->codigo() }}</span>
                                            <span class="text-xs text-gray-500 dark:text-cerberus-light">{{ $pieza->labelCondicion() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if ($sinId > 0)
                            <x-form.input label="Unidades sin serial que van (hay {{ $sinId }})" type="number" wire:model="sinSerial"
                                :error="$errors->first('sinSerial')" />
                        @endif
                    @endif

                    @error('piezaIds')
                        <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-sm
                                    bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300
                                    border border-red-200 dark:border-red-500/30">
                            <span class="material-icons text-base">error_outline</span>{{ $message }}
                        </div>
                    @enderror

                    <p class="text-xs text-gray-500 dark:text-cerberus-light flex items-start gap-1.5">
                        <span class="material-icons text-sm">info</span>
                        En la otra empresa entra a la variante con el mismo nombre (se crea si no existe). Las piezas siguen siendo las mismas, con su serial y su recorrido.
                    </p>

                    <x-form.textarea label="Observaciones (opcional)" wire:model="observaciones" rows="2" placeholder="Ej: préstamo de stock para el caso #48" />
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="guardar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition
                               flex items-center gap-2 disabled:opacity-60">
                        <span class="material-icons text-sm">swap_horiz</span> Trasladar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
