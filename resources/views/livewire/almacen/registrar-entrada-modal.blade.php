<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 90vh;">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">add_box</span>
                        Registrar entrada
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                @if ($recienRegistradas)
                    <div class="px-6 py-8 space-y-4 flex-1 text-center">
                        <span class="material-icons text-4xl text-green-500">check_circle</span>
                        <p class="text-sm text-gray-700 dark:text-white">{{ $resumen }}</p>
                        <a href="{{ $this->urlEtiquetas }}" target="_blank"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition">
                            <span class="material-icons text-sm">print</span> Imprimir etiquetas ({{ count($recienRegistradas) }})
                        </a>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                        <button wire:click="close"
                            class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white
                                   hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                            Cerrar
                        </button>
                    </div>
                @else
                <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">

                    @if (Auth::user()->hasRole('Administrador'))
                        <x-form.select label="Empresa" placeholder="Selecciona una empresa" :options="$this->empresasOpciones"
                            wire:model.live="empresa_id" :error="$errors->first('empresa_id')" required />
                    @endif

                    {{-- 1. Familia --}}
                    <x-form.select label="Familia" placeholder="¿Qué tipo de pieza entra?"
                        :options="$this->familias->mapWithKeys(fn ($f) => [$f->id => $f->nombre . ($f->esAGranel() ? ' · a granel' : ' · por unidad')])"
                        wire:model.live="familiaId" :error="$errors->first('familiaId')" required />

                    @if ($this->familia)
                        {{-- 2. Nueva o usada (por unidad) --}}
                        @unless ($this->familia->esAGranel())
                            <x-form.select label="Condición" :options="\App\Models\PiezaExtraida::CONDICIONES"
                                wire:model.live="condicion" :error="$errors->first('condicion')" />

                            @if ($condicion === \App\Models\PiezaExtraida::CONDICION_REUTILIZADO)
                                <x-form.select label="Equipo de origen" placeholder="¿De qué equipo salió?" searchable
                                    :options="$this->equiposOpciones" wire:model.live="equipoOrigenId"
                                    :error="$errors->first('equipoOrigenId')" required />
                            @endif
                        @endunless

                        @if ($this->desdeFichaDelEquipo)
                            {{-- 3a. Usada con ficha: se elige de lo que el equipo tiene en su ficha --}}
                            @if ($this->equipoOrigen)
                                @if (! $this->atributoEnEquipo)
                                    <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                                bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300
                                                border border-amber-200 dark:border-amber-500/30">
                                        <span class="material-icons text-sm">warning</span>
                                        <span>La categoría {{ $this->equipoOrigen->categoria->nombre ?? '' }} no tiene ninguna característica vinculada a {{ $this->familia->nombre }}:
                                            no se puede comprobar qué tenía el equipo. Vincúlala en Configuración → Atributos.</span>
                                    </div>
                                @elseif ($this->fichaDelEquipo->isEmpty())
                                    <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                                bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300
                                                border border-amber-200 dark:border-amber-500/30">
                                        <span class="material-icons text-sm">warning</span>
                                        <span>Según su ficha, {{ $this->equipoOrigen->codigo_interno }} no tiene {{ $this->familia->nombre }}.
                                            Si de verdad salió de ahí, corrige primero la ficha del equipo.</span>
                                    </div>
                                @else
                                    <div>
                                        <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">¿Cuál salió de {{ $this->equipoOrigen->codigo_interno }}?</p>
                                        <p class="text-xs text-gray-500 dark:text-cerberus-light mb-2">Es lo que dice su ficha. Lo que marques sale de su ficha y entra al almacén como usado.</p>
                                        <div class="space-y-1 rounded-lg border border-gray-200 dark:border-cerberus-steel/50 p-1.5">
                                            @foreach ($this->fichaDelEquipo as $entrada)
                                                <label wire:key="origen-{{ $entrada->id }}"
                                                    class="flex items-center gap-2 text-sm rounded-md px-2 py-1.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-cerberus-steel/10">
                                                    <input type="checkbox" value="{{ $entrada->id }}" wire:model.live="instanciasOrigen" class="rounded text-cerberus-primary">
                                                    <span class="text-gray-900 dark:text-white">{{ $entrada->descripcion }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('instanciasOrigen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            @endif
                        @else
                            {{-- 3b. Variante: por la ficha, o elegida/nueva --}}
                            @if ($this->usaFicha)
                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">Ficha técnica</p>
                                    @include('livewire.almacen.partials.ficha-campos', ['familia' => $this->familia])
                                </div>
                            @else
                                <x-form.select label="Variante" placeholder="Elige la variante…"
                                    :options="$this->variantes->mapWithKeys(fn ($v) => [$v->id => $v->nombre . ' (' . $v->stock_actual . ' en stock)'])->put('nueva', '+ Nueva variante…')"
                                    wire:model.live="componenteId" :error="$errors->first('componenteId')" required />

                                @if ($componenteId === 'nueva')
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4">
                                        <div class="sm:col-span-2">
                                            <x-form.input label="Nombre de la variante" wire:model.live.500ms="nuevoNombre"
                                                placeholder="Ej: Bisagra HP 14&quot;" :error="$errors->first('nuevoNombre')" required />
                                        </div>
                                        <x-form.input label="Se cuenta por" wire:model="nuevaUnidad" placeholder="unidad, par…" :error="$errors->first('nuevaUnidad')" />
                                    </div>
                                @endif
                            @endif

                            @if ($this->nombreVariante)
                                <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm
                                            bg-blue-50 dark:bg-blue-500/10 text-blue-800 dark:text-blue-200
                                            border border-blue-200 dark:border-blue-500/30">
                                    <span class="material-icons text-base">inventory_2</span>
                                    <span>Entra a: <b>{{ $this->nombreVariante }}</b></span>
                                </div>
                            @endif

                            @if ($this->avisoVariante)
                                <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                            bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300
                                            border border-amber-200 dark:border-amber-500/30">
                                    <span class="material-icons text-sm">warning</span>
                                    <span>Abriste «{{ $this->avisoVariante }}», pero con esta ficha la entrada va a otra variante.
                                        Si «{{ $this->avisoVariante }}» es esta misma pieza, edítala primero (Editar) para ponerle su ficha.</span>
                                </div>
                            @endif

                            <x-form.input label="Cantidad" type="number" wire:model.live="cantidad" :error="$errors->first('cantidad')" required />
                        @endif

                        @if ($this->familia->esAGranel())
                            <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                        bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300
                                        border border-purple-200 dark:border-purple-500/30">
                                <span class="material-icons text-sm">inventory</span>
                                <span>{{ $this->familia->nombre }} es a granel: solo se suma la cantidad, sin seriales.</span>
                            </div>
                            <x-form.input label="Motivo (opcional)" wire:model="motivo" placeholder="Ej: Compra, donación…" />
                        @else
                            @if ($condicion === \App\Models\PiezaExtraida::CONDICION_REUTILIZADO && ! $this->usaFicha)
                                <p class="text-xs text-gray-500 dark:text-cerberus-light flex items-start gap-1.5">
                                    <span class="material-icons text-sm">info</span>
                                    {{ $this->familia->nombre }} no tiene ficha: el equipo de origen queda como lo declaras (no se puede comprobar).
                                </p>
                            @endif
                            <x-form.textarea label="Seriales (recomendado)" wire:model="seriales" rows="3"
                                placeholder="Uno por línea, en el mismo orden que las unidades"
                                hint="Las unidades sin serial reciben un código interno (AUTO-…)." :error="$errors->first('seriales')" />
                        @endif

                        <x-form.textarea label="Observaciones (opcional)" wire:model="observaciones" rows="2" />
                    @endif
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="guardar" wire:loading.attr="disabled" @disabled(! $this->familia)
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition
                               flex items-center gap-2 disabled:opacity-60">
                        <span class="material-icons text-sm">save</span> Registrar entrada
                    </button>
                </div>
                @endif
            </div>
        </div>
    @endif
</div>
