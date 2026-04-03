<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $categoriaId ? 'edit' : 'add_circle' }}</span>
                        {{ $categoriaId ? 'Editar categoría' : 'Nueva categoría' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    <x-form.input
                        label="Nombre"
                        wire:model="nombre"
                        placeholder="Ej: Laptop, Desktop, Servidor..."
                        :error="$errors->first('nombre')"
                        required
                    />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                            Descripción <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <textarea wire:model="descripcion" rows="2"
                            placeholder="Descripción breve del tipo de equipo..."
                            class="w-full rounded-lg px-4 py-2 text-sm resize-none
                                   bg-white dark:bg-cerberus-dark
                                   border border-gray-300 dark:border-cerberus-steel
                                   text-gray-900 dark:text-white
                                   placeholder-gray-400 dark:placeholder-gray-500
                                   focus:outline-none focus:ring-2
                                   focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                   dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                   @error('descripcion') border-red-400 @enderror">
                        </textarea>
                        @error('descripcion')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Toggle asignable --}}
                    <div class="flex items-center justify-between py-3 px-4
                                bg-gray-50 dark:bg-cerberus-dark/50
                                border border-gray-200 dark:border-cerberus-steel/50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white">Asignable a usuarios</p>
                            <p class="text-xs text-gray-500 dark:text-cerberus-light mt-0.5">
                                Permite asignar equipos de esta categoría a personas.
                            </p>
                        </div>
                        <button wire:click="$set('asignable', {{ $asignable ? 'false' : 'true' }})" type="button"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                   {{ $asignable ? 'bg-[#1E40AF]' : 'bg-gray-300 dark:bg-cerberus-steel' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                         {{ $asignable ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
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