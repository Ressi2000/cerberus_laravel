<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                {{-- Cabecera --}}
                <div class="flex items-center justify-between px-6 py-4
                            border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">
                            {{ $departamentoId ? 'edit' : 'add_circle' }}
                        </span>
                        {{ $departamentoId ? 'Editar departamento' : 'Nuevo departamento' }}
                    </h2>
                    <button wire:click="close"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="px-6 py-5 space-y-4">

                    <x-form.input
                        label="Nombre"
                        wire:model="nombre"
                        placeholder="Ej: Tecnología, Recursos Humanos..."
                        :error="$errors->first('nombre')"
                        required
                    />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                            Descripción <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <textarea wire:model="descripcion" rows="2"
                            placeholder="Descripción breve del departamento..."
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

                    <x-form.select
                        label="Empresa"
                        :options="$this->empresas"
                        wire:model.live="empresa_id"
                        hint="Sin empresa = departamento global (visible en todas las empresas)."
                    />

                    @if (! $empresa_id)
                        <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                    bg-blue-50 dark:bg-cerberus-primary/10
                                    border border-blue-200 dark:border-cerberus-primary/30
                                    text-blue-700 dark:text-cerberus-accent">
                            <span class="material-icons text-sm mt-0.5">info</span>
                            Sin empresa asignada, este departamento será
                            <strong class="mx-0.5">global</strong>
                            y aparecerá en los selects de todas las empresas.
                        </div>
                    @endif

                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4
                            border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg
                               bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
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