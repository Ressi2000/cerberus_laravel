<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        max-h-[90vh] flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 flex-shrink-0 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $depositoId ? 'edit' : 'warehouse' }}</span>
                        {{ $depositoId ? 'Editar depósito' : 'Nuevo depósito' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">

                    @if (Auth::user()->hasRole('Administrador'))
                        <x-form.select
                            label="Empresa"
                            placeholder="Selecciona una empresa"
                            :options="$this->empresasOpciones"
                            wire:model.live="empresa_id"
                            :error="$errors->first('empresa_id')"
                            :disabled="(bool) $depositoId"
                            required
                        />
                    @endif

                    <x-form.input
                        label="Nombre"
                        wire:model="nombre"
                        placeholder="Ej: Depósito Principal, Depósito de Tóxicos..."
                        :error="$errors->first('nombre')"
                        required
                    />

                    <div wire:key="deposito-ubicacion-select-{{ $empresa_id ?: 'sin-empresa' }}">
                        <x-form.select
                            searchable
                            label="Ubicación (opcional)"
                            placeholder="{{ $empresa_id ? 'Sin especificar' : 'Primero selecciona la empresa' }}"
                            :options="$this->ubicacionesOpciones"
                            wire:model="ubicacion_id"
                            :disabled="! $empresa_id"
                            hint="Apóyate en una ubicación ya existente si quieres decir dónde queda este depósito."
                            :error="$errors->first('ubicacion_id')"
                        />
                    </div>

                    <x-form.textarea
                        label="Descripción"
                        wire:model="descripcion"
                        rows="2"
                        placeholder="Notas sobre este depósito..."
                        :error="$errors->first('descripcion')"
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
                        <span wire:loading.remove wire:target="guardar">Guardar</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
