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
                        {{-- 2. Variante --}}
                        @if ($this->usaFicha)
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">Ficha técnica</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                    @foreach ($this->familia->campos as $campo)
                                        <div wire:key="ficha-{{ $campo['id'] }}">
                                            @if (($campo['tipo'] ?? '') === 'select')
                                                <x-form.select :label="$campo['nombre']" placeholder="Elige…"
                                                    :options="collect($campo['opciones'] ?? [])->mapWithKeys(fn ($o) => [$o => $o])"
                                                    wire:model.live="valores.{{ $campo['id'] }}"
                                                    :error="$errors->first('valores.' . $campo['id'])" />
                                            @else
                                                <x-form.input :label="$campo['nombre']"
                                                    type="{{ in_array($campo['tipo'] ?? '', ['integer', 'decimal']) ? 'number' : 'text' }}"
                                                    wire:model.live.500ms="valores.{{ $campo['id'] }}"
                                                    :error="$errors->first('valores.' . $campo['id'])" />
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
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

                        {{-- 3. Cantidad y detalle --}}
                        <x-form.input label="Cantidad" type="number" wire:model.live="cantidad" :error="$errors->first('cantidad')" required />

                        @if ($this->familia->esAGranel())
                            <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                        bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300
                                        border border-purple-200 dark:border-purple-500/30">
                                <span class="material-icons text-sm">inventory</span>
                                <span>{{ $this->familia->nombre }} es a granel: solo se suma la cantidad, sin seriales.</span>
                            </div>
                            <x-form.input label="Motivo (opcional)" wire:model="motivo" placeholder="Ej: Compra, donación…" />
                        @else
                            <x-form.select label="Condición" :options="\App\Models\PiezaExtraida::CONDICIONES"
                                wire:model.live="condicion" :error="$errors->first('condicion')" />

                            @if ($condicion === \App\Models\PiezaExtraida::CONDICION_REUTILIZADO)
                                <x-form.select label="Equipo de origen" placeholder="¿De qué equipo salió?" searchable
                                    :options="$this->equiposOpciones" wire:model="equipoOrigenId"
                                    :error="$errors->first('equipoOrigenId')" required />
                            @endif

                            <x-form.textarea label="Seriales (recomendado)" wire:model="seriales" rows="3"
                                placeholder="Uno por línea, en el mismo orden que las unidades"
                                hint="Las unidades sin serial reciben un código interno (AUTO-…)." />
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
            </div>
        </div>
    @endif
</div>
