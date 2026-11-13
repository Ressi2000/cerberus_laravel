<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl max-h-[92vh] flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $hostId ? 'edit' : 'add_circle' }}</span>
                        {{ $hostId ? 'Editar host' : 'Agregar host' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 overflow-y-auto">

                    @if ($this->empresasOpciones->count() > 1)
                        <x-form.select label="Empresa" placeholder="Selecciona una empresa" :options="$this->empresasOpciones"
                            wire:model="empresa_id" :error="$errors->first('empresa_id')" required />
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <x-form.input label="IP o nombre DNS" wire:model="host" placeholder="192.168.1.1 o srv.miempresa.com"
                            autocomplete="off" :error="$errors->first('host')" required />

                        <x-form.input label="Nombre" wire:model="nombre" placeholder="Ej. Router sucursal centro"
                            hint="Si lo dejas vacío se usa la IP." :error="$errors->first('nombre')" />

                        <div>
                            <x-form.input label="Tipo" wire:model="tipo" list="monitor-tipos" placeholder="Router, Switch..."
                                hint="Escribe uno nuevo o elige de la lista." :error="$errors->first('tipo')" required />
                            <datalist id="monitor-tipos">
                                @foreach ($this->tiposSugeridos as $t)
                                    <option value="{{ $t }}">
                                @endforeach
                            </datalist>
                        </div>

                        <x-form.input label="Proveedor / enlace" wire:model="proveedor" placeholder="ISP 1, Enlace dedicado..."
                            :error="$errors->first('proveedor')" />
                    </div>

                    <x-form.input label="Puerto TCP de respaldo" type="number" wire:model="puerto_tcp" placeholder="80, 443, 22..."
                        hint="Opcional. Si el equipo bloquea el ping, se considera activo cuando este puerto acepta conexiones."
                        :error="$errors->first('puerto_tcp')" />

                    {{-- Vínculo con el inventario --}}
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">Equipo del inventario <span class="font-normal text-gray-400 dark:text-cerberus-steel">(opcional)</span></p>

                        @if ($this->equipoSeleccionado)
                            <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg
                                        bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50">
                                <span class="text-sm text-gray-900 dark:text-white">
                                    <span class="material-icons text-base align-middle text-cerberus-accent">devices</span>
                                    {{ $this->equipoSeleccionado->codigo_interno }}
                                    <span class="text-xs text-gray-500 dark:text-cerberus-light">{{ $this->equipoSeleccionado->categoria?->nombre }}</span>
                                </span>
                                <button type="button" wire:click="quitarEquipo" class="text-xs text-red-500 hover:underline">Quitar</button>
                            </div>
                        @else
                            <x-form.input wire:model.live.400ms="equipoBusqueda" placeholder="Buscar por código interno..." autocomplete="off" />
                            @if ($this->equiposEncontrados->isNotEmpty())
                                <div class="mt-1 rounded-lg border border-gray-200 dark:border-cerberus-steel/50 divide-y divide-gray-100 dark:divide-cerberus-steel/30 overflow-hidden">
                                    @foreach ($this->equiposEncontrados as $eq)
                                        <button type="button" wire:click="elegirEquipo({{ $eq->id }})" wire:key="eq-{{ $eq->id }}"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-cerberus-steel/20 text-gray-900 dark:text-white">
                                            {{ $eq->codigo_interno }}
                                            <span class="text-xs text-gray-500 dark:text-cerberus-light">{{ $eq->categoria?->nombre }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @elseif (mb_strlen(trim($equipoBusqueda)) >= 2)
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-1">Sin coincidencias.</p>
                            @endif
                        @endif
                    </div>

                    <x-form.textarea label="Observaciones" wire:model="observaciones" rows="2" placeholder="Ubicación, contacto del proveedor, notas..." />

                    @error('general') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
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
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
