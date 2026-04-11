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
                            {{ $cargoId ? 'edit' : 'add_circle' }}
                        </span>
                        {{ $cargoId ? 'Editar cargo' : 'Nuevo cargo' }}
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
                        placeholder="Ej: Analista de Sistemas, Gerente de TI..."
                        :error="$errors->first('nombre')"
                        required
                    />

                    {{-- Empresa → limpia departamento al cambiar --}}
                    <x-form.select
                        label="Empresa"
                        :options="$this->empresas"
                        wire:model.live="empresa_id"
                        hint="Sin empresa = cargo global (visible en todas las empresas)."
                    />

                    {{-- Departamento — reactivo según empresa --}}
                    <x-form.select
                        label="Departamento"
                        :options="$this->departamentos"
                        wire:model="departamento_id"
                        :error="$errors->first('departamento_id')"
                        required
                        :hint="! $empresa_id
                            ? 'Mostrando departamentos globales. Selecciona una empresa para ver los suyos también.'
                            : 'Departamentos globales + los de la empresa seleccionada.'"
                    />

                    {{-- Aviso global --}}
                    @if (! $empresa_id)
                        <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                    bg-blue-50 dark:bg-cerberus-primary/10
                                    border border-blue-200 dark:border-cerberus-primary/30
                                    text-blue-700 dark:text-cerberus-accent">
                            <span class="material-icons text-sm mt-0.5">info</span>
                            Sin empresa asignada, este cargo será
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