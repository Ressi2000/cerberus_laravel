<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $componenteId ? 'edit' : 'add_circle' }}</span>
                        {{ $componenteId ? 'Editar componente' : 'Nuevo componente' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    <x-form.select
                        label="Empresa"
                        placeholder="Selecciona una empresa"
                        :options="$this->empresasOpciones"
                        wire:model.live="empresa_id"
                        :error="$errors->first('empresa_id')"
                        required
                    />

                    <x-form.select
                        label="Familia"
                        placeholder="¿Qué tipo de pieza es?"
                        :options="$this->familiasOpciones->mapWithKeys(fn ($f) => [$f->id => $f->nombre . ($f->control === 'granel' ? ' · a granel' : ' · por unidad')])"
                        wire:model.live="familia_id"
                        :error="$errors->first('familia_id')"
                        required
                    />

                    @if ($this->piezasQueSeAgrupan > 0)
                        <div class="-mt-2 flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                    bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300
                                    border border-purple-200 dark:border-purple-500/30">
                            <span class="material-icons text-sm">info</span>
                            <span>Esa familia es a granel: las {{ $this->piezasQueSeAgrupan }} pieza(s) en almacén pasarán a contarse solo por cantidad. El stock no cambia y lo ya usado conserva su historial.</span>
                        </div>
                    @endif

                    @if ($this->usaFicha)
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">Ficha técnica</p>
                            <p class="text-xs text-gray-500 dark:text-cerberus-light -mt-1">
                                {{ $this->familia->nombre }} tiene ficha: la variante se define con ella y su nombre sale solo.
                            </p>
                            @include('livewire.almacen.partials.ficha-campos', ['familia' => $this->familia])
                            @if ($componenteId && empty(array_filter($valores)))
                                <p class="text-xs text-amber-700 dark:text-amber-300">
                                    Esta variante se creó sin ficha («{{ $nombre }}»). Complétala: sus piezas en el almacén quedarán con estos valores.
                                </p>
                            @endif
                            @if ($this->nombreFicha)
                                <p class="text-xs text-gray-600 dark:text-cerberus-light">Se llamará: <b>{{ $this->nombreFicha }}</b></p>
                            @endif
                            @error('valores') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    @else
                    <x-form.input
                        label="Nombre (variante)"
                        wire:model.live.400ms="nombre"
                        placeholder="Ej: Bisagra HP 14"
                        autocomplete="off"
                        :error="$errors->first('nombre')"
                        required
                    />

                    @if ($this->coincidencias->isNotEmpty())
                        <div class="-mt-2 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30
                                    rounded-lg p-3 space-y-2">
                            <p class="text-xs font-medium text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                <span class="material-icons text-sm">info</span>
                                Ya existe algo parecido — ¿es esto?
                            </p>
                            @foreach ($this->coincidencias as $match)
                                <div class="flex items-center justify-between gap-3 bg-white dark:bg-cerberus-dark
                                            border border-gray-200 dark:border-cerberus-steel/50 rounded-lg px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $match->nombre }}</p>
                                        <p class="text-xs text-gray-500 dark:text-cerberus-light">{{ $match->stock_actual }} {{ $match->unidad }} en stock</p>
                                    </div>
                                    <button type="button" wire:click="usarExistente({{ $match->id }})"
                                        class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-lg
                                               bg-amber-600 hover:bg-amber-700 text-white transition">
                                        Usar este
                                    </button>
                                </div>
                            @endforeach
                            <p class="text-xs text-amber-700/80 dark:text-amber-400/70">
                                Si de verdad es otra cosa, sigue escribiendo y créalo normal.
                            </p>
                        </div>
                    @endif

                    @endif

                    <x-form.textarea
                        label="Descripción"
                        wire:model="descripcion"
                        rows="2"
                        placeholder="Detalle opcional del componente..."
                    />

                    <x-form.input
                        label="Unidad"
                        wire:model="unidad"
                        placeholder="unidad, par, metro..."
                        hint="Cómo se cuenta este componente: unidad, par, metro, caja..."
                        :error="$errors->first('unidad')"
                    />

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
                        <span wire:loading.remove wire:target="guardar">Guardar</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
