<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 90vh;">

                <div class="flex items-center justify-between px-6 py-4 flex-shrink-0 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">add_box</span>
                        Registrar pieza nueva
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">

                    <p class="text-xs text-gray-500 dark:text-cerberus-light">
                        Para una pieza que ya es un atributo de algún equipo (RAM, disco...) — con sus mismas
                        características. Si es algo sin atributo (bisagras, tornillos...), usa «Nuevo componente».
                    </p>

                    <x-form.select
                        label="Empresa"
                        placeholder="Selecciona una empresa"
                        :options="$this->empresasOpciones"
                        wire:model.live="empresa_id"
                        :error="$errors->first('empresa_id')"
                        required
                    />

                    <x-form.select
                        label="¿A cuál atributo corresponde?"
                        placeholder="Selecciona el atributo..."
                        :options="$this->atributosOpciones"
                        wire:model.live="atributoId"
                        :error="$errors->first('atributoId')"
                        hint="Se muestra con su categoría porque, por ejemplo, «RAM — Desktop» y «RAM — Laptop» pueden tener características distintas."
                        required
                    />

                    @if ($this->atributoSeleccionado)
                        @php($atributo = $this->atributoSeleccionado)

                        <div class="bg-gray-50 dark:bg-cerberus-dark/50 border border-gray-200 dark:border-cerberus-steel/50
                                    rounded-lg p-4 space-y-3">

                            @if ($atributo->esGrupo())
                                @foreach ($atributo->sub_campos ?? [] as $sc)
                                    <div wire:key="sc-{{ $sc['id'] }}">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-cerberus-accent mb-1">
                                            {{ $sc['nombre'] }}
                                            @if (! empty($sc['requerido'])) <span class="text-red-400">*</span> @endif
                                        </label>

                                        @if ($sc['tipo'] === 'select')
                                            <select wire:model="valores.{{ $sc['id'] }}"
                                                class="w-full rounded-lg px-3 py-1.5 text-sm
                                                       bg-white dark:bg-cerberus-dark
                                                       border border-gray-300 dark:border-cerberus-steel
                                                       text-gray-900 dark:text-white
                                                       focus:outline-none focus:ring-2
                                                       focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                                       dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary">
                                                <option value="">Seleccione...</option>
                                                @foreach ($sc['opciones'] ?? [] as $opcion)
                                                    <option value="{{ $opcion }}">{{ $opcion }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($sc['tipo'] === 'boolean')
                                            <div class="flex items-center gap-4 h-[34px]">
                                                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700 dark:text-white">
                                                    <input type="radio" wire:model="valores.{{ $sc['id'] }}" value="1"
                                                        class="text-cerberus-primary focus:ring-cerberus-primary">
                                                    Sí
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700 dark:text-white">
                                                    <input type="radio" wire:model="valores.{{ $sc['id'] }}" value="0"
                                                        class="text-cerberus-primary focus:ring-cerberus-primary">
                                                    No
                                                </label>
                                            </div>
                                        @else
                                            @php
                                                $inputType = match ($sc['tipo']) {
                                                    'integer', 'decimal' => 'number',
                                                    'date'               => 'date',
                                                    default              => 'text',
                                                };
                                            @endphp
                                            <input type="{{ $inputType }}"
                                                wire:model="valores.{{ $sc['id'] }}"
                                                @if ($sc['tipo'] === 'decimal') step="0.01" @endif
                                                class="w-full rounded-lg px-3 py-1.5 text-sm
                                                       bg-white dark:bg-cerberus-dark
                                                       border border-gray-300 dark:border-cerberus-steel
                                                       text-gray-900 dark:text-white
                                                       focus:outline-none focus:ring-2
                                                       focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                                       dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary">
                                        @endif
                                        @error("valores.{$sc['id']}")
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            @else
                                <x-form.input
                                    label="{{ $atributo->nombre }}"
                                    wire:model="valores.valor"
                                    :error="$errors->first('valores.valor')"
                                    required
                                />
                            @endif
                        </div>

                        <x-form.input
                            label="Cantidad"
                            type="number"
                            min="1"
                            wire:model="cantidad"
                            hint="Si llegaron varias unidades idénticas (misma especificación), regístralas juntas aquí."
                            :error="$errors->first('cantidad')"
                            required
                        />
                    @endif

                    <x-form.textarea
                        label="Observaciones (opcional)"
                        wire:model="observaciones"
                        rows="2"
                        placeholder="Ej: factura #1234, proveedor..."
                    />
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 flex-shrink-0 border-t border-gray-100 dark:border-cerberus-steel">
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
