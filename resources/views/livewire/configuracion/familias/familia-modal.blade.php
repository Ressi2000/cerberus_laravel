<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-2xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 90vh;">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $familiaId ? 'edit' : 'category' }}</span>
                        {{ $familiaId ? 'Editar familia' : 'Nueva familia de piezas' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form.input label="Nombre" wire:model="nombre" placeholder="Ej: RAM, Discos…" :error="$errors->first('nombre')" required />
                        <x-form.select label="Cómo se controla" :options="$controles" wire:model="control" :error="$errors->first('control')" />
                    </div>
                    <x-form.textarea label="Descripción (opcional)" wire:model="descripcion" rows="2" placeholder="Para qué sirve esta familia…" />

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">Ficha técnica</label>
                            <button type="button" wire:click="agregarCampo"
                                class="flex items-center gap-1 text-xs px-2 py-1 rounded-lg text-teal-700 dark:text-teal-300
                                       bg-teal-50 dark:bg-teal-500/15 hover:bg-teal-100 dark:hover:bg-teal-500/25 transition">
                                <span class="material-icons text-sm">add</span> Campo
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-cerberus-light">
                            Estos campos los comparten todas las categorías que usen la familia.
                            Marca <span class="material-icons text-xs align-middle">rule</span> <b>debe coincidir</b> en lo que hace que una pieza entre físicamente (ej. Tipo DDR4, Formato SO-DIMM).
                        </p>
                        @error('campos')
                            <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror

                        @foreach ($campos as $i => $campo)
                            <div wire:key="campo-{{ $campo['id'] }}"
                                 class="p-3 rounded-lg border border-gray-200 dark:border-cerberus-steel/40 bg-gray-50 dark:bg-cerberus-dark/40 space-y-2">
                                <div class="grid gap-2 items-start" style="grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) auto;">
                                    <div>
                                        <input type="text" wire:model="campos.{{ $i }}.nombre" placeholder="Ej: Capacidad (GB)"
                                            class="w-full rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-cerberus-dark
                                                   border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white">
                                        @error('campos.'.$i.'.nombre') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                    <select wire:model.live="campos.{{ $i }}.tipo"
                                        class="w-full rounded-lg px-2 py-1.5 text-sm bg-white dark:bg-cerberus-dark
                                               border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white">
                                        @foreach ($tipos as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex items-center gap-0.5">
                                        <button type="button" wire:click="mover({{ $i }}, -1)" title="Subir"
                                            class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white disabled:opacity-30" @disabled($i === 0)>
                                            <span class="material-icons text-base">arrow_upward</span>
                                        </button>
                                        <button type="button" wire:click="mover({{ $i }}, 1)" title="Bajar"
                                            class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white disabled:opacity-30" @disabled($i === count($campos) - 1)>
                                            <span class="material-icons text-base">arrow_downward</span>
                                        </button>
                                        <button type="button" wire:click="quitarCampo({{ $i }})" title="Quitar campo"
                                            class="p-1 rounded text-red-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
                                            <span class="material-icons text-base">delete</span>
                                        </button>
                                    </div>
                                </div>

                                @if (($campo['tipo'] ?? '') === 'select')
                                    <div>
                                        <textarea wire:model="campos.{{ $i }}.opciones_raw" rows="3" placeholder="Una opción por línea&#10;DDR3&#10;DDR4"
                                            class="w-full rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-cerberus-dark
                                                   border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white"></textarea>
                                        @error('campos.'.$i.'.opciones_raw') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-4 text-xs text-gray-600 dark:text-cerberus-light">
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" wire:model="campos.{{ $i }}.requerido"
                                            class="rounded border-gray-300 dark:border-cerberus-steel dark:bg-cerberus-dark text-cerberus-primary">
                                        Obligatorio
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" wire:model="campos.{{ $i }}.debe_coincidir"
                                            class="rounded border-gray-300 dark:border-cerberus-steel dark:bg-cerberus-dark text-teal-600">
                                        <span class="material-icons text-xs text-teal-600 dark:text-teal-400">rule</span>
                                        Debe coincidir para que la pieza encaje
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
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
                        <span class="material-icons text-sm">save</span> Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
