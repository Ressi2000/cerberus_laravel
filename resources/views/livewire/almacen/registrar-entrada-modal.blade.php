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
                        @php $usada = ! $this->familia->esAGranel() && $condicion === \App\Models\PiezaExtraida::CONDICION_REUTILIZADO; @endphp

                        {{-- 2. Nueva o usada (por unidad) --}}
                        @unless ($this->familia->esAGranel())
                            <x-form.select label="Condición" :options="\App\Models\PiezaExtraida::CONDICIONES"
                                wire:model.live="condicion" :error="$errors->first('condicion')" />
                        @endunless

                        @unless ($this->desdeFichaDelEquipo)
                            {{-- 3. Variante: por la ficha, o elegida/nueva (usada con ficha: sale de la ficha de cada equipo) --}}
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

                            @unless ($usada)
                                <x-form.input label="Cantidad" type="number" wire:model.live="cantidad" :error="$errors->first('cantidad')" required />
                            @endunless
                        @endunless

                        {{-- 4. Usada: de qué equipo(s) salió — puede ser de varios --}}
                        @if ($usada)
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">¿De qué equipo(s) salió?</p>
                                    <p class="text-xs text-gray-500 dark:text-cerberus-light">
                                        @if ($this->usaFicha)
                                            En cada equipo, marca lo que salió según su ficha: sale de su ficha y entra al almacén como usado.
                                        @else
                                            {{ $this->familia->nombre }} no tiene ficha: el equipo de origen queda como lo declaras (no se puede comprobar).
                                        @endif
                                    </p>
                                </div>

                                @foreach ($origenes as $i => $origen)
                                    @php $d = $this->detalleOrigenes[$i] ?? null; @endphp
                                    <div wire:key="origen-{{ $i }}"
                                         class="rounded-lg border border-gray-200 dark:border-cerberus-steel/50 bg-gray-50 dark:bg-cerberus-dark/40 p-3 space-y-2">
                                        <div class="flex items-start gap-2">
                                            <div class="flex-1 min-w-0">
                                                <x-form.select :label="'Equipo de origen' . (count($origenes) > 1 ? ' ' . ($i + 1) : '')" placeholder="¿De qué equipo salió?" searchable
                                                    :options="$this->equiposOpciones" wire:model.live="origenes.{{ $i }}.equipo"
                                                    :error="$errors->first('origenes.' . $i . '.equipo')" required />
                                            </div>
                                            @if (count($origenes) > 1)
                                                <button type="button" wire:click="quitarOrigen({{ $i }})" title="Quitar este equipo"
                                                    class="mt-7 text-gray-400 hover:text-red-500">
                                                    <span class="material-icons text-base">close</span>
                                                </button>
                                            @endif
                                        </div>

                                        @if ($d?->equipo)
                                            @if ($this->usaFicha)
                                                @if (! $d->atributo)
                                                    <p class="text-xs text-amber-700 dark:text-amber-300">
                                                        La categoría {{ $d->equipo->categoria->nombre ?? '' }} no tiene {{ $this->familia->nombre }} vinculada a la familia:
                                                        no se puede comprobar qué tenía. Vincúlala en Configuración → Atributos.
                                                    </p>
                                                @elseif ($d->ficha->isEmpty())
                                                    <p class="text-xs text-amber-700 dark:text-amber-300">
                                                        Según su ficha, {{ $d->equipo->codigo_interno }} no tiene {{ $this->familia->nombre }}. Si de verdad salió de ahí, corrige primero la ficha del equipo.
                                                    </p>
                                                @else
                                                    <div class="space-y-1 rounded-lg border border-gray-200 dark:border-cerberus-steel/50 bg-white dark:bg-cerberus-dark p-1.5">
                                                        @foreach ($d->ficha as $entrada)
                                                            <label wire:key="origen-{{ $i }}-{{ $entrada->id }}"
                                                                class="flex items-center gap-2 text-sm rounded-md px-2 py-1.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-cerberus-steel/10">
                                                                <input type="checkbox" value="{{ $entrada->id }}" wire:model.live="origenes.{{ $i }}.instancias" class="rounded text-cerberus-primary">
                                                                <span class="text-gray-900 dark:text-white">{{ $entrada->descripcion }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @error('origenes.' . $i . '.instancias') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                                            @else
                                                <x-form.input label="¿Cuántas salieron de este equipo?" type="number" wire:model.live="origenes.{{ $i }}.cantidad"
                                                    :error="$errors->first('origenes.' . $i . '.cantidad')" />
                                            @endif

                                            <x-form.textarea label="Seriales (uno por línea, opcional)" wire:model="origenes.{{ $i }}.seriales" rows="2"
                                                placeholder="En el mismo orden" :error="$errors->first('origenes.' . $i . '.seriales')" />
                                        @endif
                                    </div>
                                @endforeach

                                <button type="button" wire:click="agregarOrigen"
                                    class="text-sm text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1">
                                    <span class="material-icons text-base">add</span> Otro equipo de origen
                                </button>

                                @if ($this->resumenOrigenes)
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm
                                                bg-blue-50 dark:bg-blue-500/10 text-blue-800 dark:text-blue-200
                                                border border-blue-200 dark:border-blue-500/30">
                                        <span class="material-icons text-base">inventory_2</span>
                                        <span>Entran: <b>{{ $this->resumenOrigenes }}</b></span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($this->familia->esAGranel())
                            <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                        bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300
                                        border border-purple-200 dark:border-purple-500/30">
                                <span class="material-icons text-sm">inventory</span>
                                <span>{{ $this->familia->nombre }} es a granel: solo se suma la cantidad, sin seriales.</span>
                            </div>
                            <x-form.input label="Motivo (opcional)" wire:model="motivo" placeholder="Ej: Compra, donación…" />
                        @elseif (! $usada)
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
