<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-2xl bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel
                        rounded-xl shadow-xl max-h-[90vh] flex flex-col">

                {{-- Cabecera --}}
                <div class="flex items-center justify-between px-6 py-4 flex-shrink-0
                            border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $atributoId ? 'edit' : 'add_circle' }}</span>
                        {{ $atributoId ? 'Editar atributo' : 'Nuevo atributo' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- Cuerpo scrollable --}}
                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form.select
                            label="Categoría"
                            :options="$categorias"
                            wire:model="categoria_id"
                            :error="$errors->first('categoria_id')"
                            required
                        />
                        <x-form.input
                            label="Nombre del atributo"
                            wire:model="nombre"
                            placeholder="Ej: RAM (GB), Procesador, IMEI..."
                            :error="$errors->first('nombre')"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                                Tipo de dato <span class="text-red-400">*</span>
                            </label>
                            <select wire:model.live="tipo"
                                class="w-full rounded-lg px-4 py-2 text-sm
                                       bg-white dark:bg-cerberus-dark
                                       border border-gray-300 dark:border-cerberus-steel
                                       text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2
                                       focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                       dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                       @error('tipo') border-red-400 @enderror">
                                @foreach ($tiposDisponibles as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tipo')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-form.input
                            label="Orden de aparición"
                            type="number"
                            wire:model="orden"
                            placeholder="0"
                            hint="Número menor aparece primero en el formulario."
                        />
                    </div>

                    {{-- Opciones para tipo 'select' --}}
                    @if ($tipo === 'select')
                        <div class="border border-gray-200 dark:border-cerberus-steel/50 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">
                                    Opciones de la lista <span class="text-red-400">*</span>
                                </p>
                                <button wire:click="agregarOpcion" type="button"
                                    class="flex items-center gap-1 text-xs px-2 py-1 rounded-lg
                                           text-[#1E40AF] dark:text-cerberus-accent
                                           bg-[#1E40AF]/10 dark:bg-cerberus-primary/10
                                           hover:bg-[#1E40AF]/20 dark:hover:bg-cerberus-primary/20 transition">
                                    <span class="material-icons text-sm">add</span>
                                    Agregar opción
                                </button>
                            </div>
                            @error('opciones')
                                <p class="text-red-500 text-xs">{{ $message }}</p>
                            @enderror
                            @forelse ($opciones as $index => $opcion)
                                <div wire:key="opcion-{{ $opcion['id'] }}" class="flex items-center gap-2">
                                    <span class="material-icons text-gray-400 dark:text-cerberus-steel text-sm flex-shrink-0">
                                        drag_handle
                                    </span>
                                    <input wire:model="opciones.{{ $index }}.valor"
                                        type="text"
                                        placeholder="Valor de la opción..."
                                        class="flex-1 rounded-lg px-3 py-1.5 text-sm
                                               bg-white dark:bg-cerberus-dark
                                               border border-gray-300 dark:border-cerberus-steel
                                               text-gray-900 dark:text-white
                                               placeholder-gray-400 dark:placeholder-gray-500
                                               focus:outline-none focus:ring-2
                                               focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                               dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                               @error('opciones.'.$index.'.valor') border-red-400 @enderror"
                                    />
                                    <button wire:click="eliminarOpcion('{{ $opcion['id'] }}')" type="button"
                                        class="flex-shrink-0 p-1 text-red-400 hover:text-red-500
                                               hover:bg-red-50 dark:hover:bg-red-500/10 rounded transition">
                                        <span class="material-icons text-sm">close</span>
                                    </button>
                                </div>
                                @error('opciones.'.$index.'.valor')
                                    <p class="text-red-500 text-xs ml-6">{{ $message }}</p>
                                @enderror
                            @empty
                                <p class="text-sm text-gray-400 dark:text-cerberus-steel italic text-center py-2">
                                    Pulsa «Agregar opción» para comenzar.
                                </p>
                            @endforelse
                        </div>
                    @endif

                    {{-- Toggles de comportamiento --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach([
                            ['requerido',        'Requerido',        'Obligatorio al crear/editar un equipo.'],
                            ['filtrable',         'Filtrable',         'Aparece como filtro en el inventario.'],
                            ['visible_en_tabla',  'Visible en tabla',  'Se muestra como columna en el listado.'],
                        ] as [$campo, $etiqueta, $hint])
                            <div class="flex items-start justify-between py-3 px-4
                                        bg-gray-50 dark:bg-cerberus-dark/50
                                        border border-gray-200 dark:border-cerberus-steel/50 rounded-lg">
                                <div class="flex-1 min-w-0 pr-3">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $etiqueta }}</p>
                                    <p class="text-xs text-gray-500 dark:text-cerberus-light mt-0.5">{{ $hint }}</p>
                                </div>
                                <button wire:click="$set('{{ $campo }}', {{ $$campo ? 'false' : 'true' }})"
                                    type="button"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 items-center
                                           rounded-full transition-colors
                                           {{ $$campo ? 'bg-[#1E40AF]' : 'bg-gray-300 dark:bg-cerberus-steel' }}">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow
                                                 transition-transform {{ $$campo ? 'translate-x-6' : 'translate-x-1' }}">
                                    </span>
                                </button>
                            </div>
                        @endforeach
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 flex-shrink-0
                            border-t border-gray-100 dark:border-cerberus-steel">
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