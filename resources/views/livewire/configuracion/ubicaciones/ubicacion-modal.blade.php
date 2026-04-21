<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div
                class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                {{-- Cabecera --}}
                <div
                    class="flex items-center justify-between px-6 py-4
                            border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">
                            {{ $ubicacionId ? 'edit' : 'add_circle' }}
                        </span>
                        {{ $ubicacionId ? 'Editar ubicación' : 'Nueva ubicación' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="px-6 py-5 space-y-4">

                    <x-form.input label="Nombre" wire:model="nombre"
                        placeholder="Ej: Sede Caracas, Oficina Maracaibo..." :error="$errors->first('nombre')" required />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                            Descripción <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <textarea wire:model="descripcion" rows="2" placeholder="Descripción breve de la ubicación..."
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

                    @unless ($es_estado)
                        <x-form.select label="Empresa" :options="$this->empresas" wire:model="empresa_id" :error="$errors->first('empresa_id')" required
                            hint="Empresa a la que pertenece esta ubicación física." />
                    @else
                        <div
                            class="rounded-lg border border-purple-200 dark:border-purple-500/40
                            bg-purple-50 dark:bg-purple-500/10 px-4 py-3">
                            <p class="text-xs text-purple-600 dark:text-purple-300 flex items-center gap-1.5">
                                <span class="material-icons text-sm">info</span>
                                Las ubicaciones foráneas no pertenecen a ninguna empresa específica.
                            </p>
                        </div>
                    @endunless

                    {{-- Toggle es_estado — elemento crítico, explicación clara --}}
                    <div
                        class="rounded-lg border {{ $es_estado
                            ? 'border-purple-200 dark:border-purple-500/40 bg-purple-50 dark:bg-purple-500/10'
                            : 'border-gray-200 dark:border-cerberus-steel/50 bg-gray-50 dark:bg-cerberus-dark/50' }} p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800 dark:text-white flex items-center gap-1.5">
                                    <span
                                        class="material-icons text-base {{ $es_estado ? 'text-purple-500' : 'text-gray-400' }}">
                                        public
                                    </span>
                                    Ubicación foránea
                                </p>
                                <p
                                    class="text-xs mt-1 {{ $es_estado ? 'text-purple-600 dark:text-purple-300' : 'text-gray-500 dark:text-cerberus-light' }}">
                                    @if ($es_estado)
                                        Activado: esta ubicación es visible para
                                        <strong>todos los analistas</strong> sin importar su empresa activa.
                                    @else
                                        Desactivado: esta ubicación solo es visible para analistas de
                                        <strong>su empresa</strong>.
                                    @endif
                                </p>
                            </div>
                            <button wire:click="$set('es_estado', {{ $es_estado ? 'false' : 'true' }})" type="button"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors
                                       {{ $es_estado ? 'bg-purple-500' : 'bg-gray-300 dark:bg-cerberus-steel' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                             {{ $es_estado ? 'translate-x-6' : 'translate-x-1' }}">
                                </span>
                            </button>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div
                    class="flex justify-end gap-3 px-6 py-4
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
                        <span wire:loading wire:target="guardar"
                            class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="guardar">Guardar</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
