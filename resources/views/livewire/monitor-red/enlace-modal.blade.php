<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-2xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl max-h-[92vh] flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">router</span>
                        {{ $enlaceId ? 'Editar enlace' : 'Nuevo enlace de internet' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 overflow-y-auto">
                    @if ($this->empresasOpciones->count() > 1)
                        <x-form.select label="Empresa / localidad" placeholder="Selecciona una empresa" :options="$this->empresasOpciones"
                            wire:model="empresa_id" :error="$errors->first('empresa_id')" required />
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <x-form.input label="Nombre" wire:model="nombre" placeholder="Fibra principal, Respaldo 4G..."
                            :error="$errors->first('nombre')" required />
                        <x-form.input label="Proveedor (ISP)" wire:model="proveedor" placeholder="Movistar, CANTV, Inter..."
                            :error="$errors->first('proveedor')" required />

                        <x-form.select label="Tipo de conexión" wire:model="tipo_conexion" placeholder="Selecciona..."
                            :options="array_combine(\App\Models\MonitorEnlace::TIPOS, \App\Models\MonitorEnlace::TIPOS)"
                            :error="$errors->first('tipo_conexion')" required />
                        <x-form.input label="Puerta de enlace (LAN)" wire:model="gateway" placeholder="192.168.1.1"
                            hint="IP del router/módem de este proveedor dentro de la localidad. Sirve para forzar la salida por este enlace al medir en sitio."
                            :error="$errors->first('gateway')" />

                        <x-form.input label="IP pública" wire:model="ip_publica" placeholder="200.44.32.12"
                            hint="Solo si es fija. Permite vigilar el enlace desde Cerberus y reconocerlo automáticamente en los tests de velocidad."
                            :error="$errors->first('ip_publica')" />
                        <x-form.input label="ASN del proveedor" wire:model="asn" placeholder="8048"
                            hint="Número de sistema autónomo del ISP. Aparece en el test de velocidad (ej. AS8048); identifica al proveedor aunque la IP pública cambie."
                            :error="$errors->first('asn')" />

                        <x-form.input label="Bajada contratada" type="number" wire:model="bajada" suffix="Mbps" placeholder="100"
                            :error="$errors->first('bajada')" />
                        <x-form.input label="Subida contratada" type="number" wire:model="subida" suffix="Mbps" placeholder="50"
                            :error="$errors->first('subida')" />
                    </div>

                    <div class="flex flex-wrap gap-x-6 gap-y-2 mb-4 text-sm text-gray-700 dark:text-cerberus-light">
                        <label class="inline-flex items-center gap-2 select-none">
                            <input type="checkbox" wire:model="principal" class="rounded border-gray-300 dark:border-cerberus-steel"> Enlace principal
                        </label>
                        <label class="inline-flex items-center gap-2 select-none">
                            <input type="checkbox" wire:model="con_cuota" class="rounded border-gray-300 dark:border-cerberus-steel"> Tiene cuota de datos (4G/LTE)
                        </label>
                        <label class="inline-flex items-center gap-2 select-none">
                            <input type="checkbox" wire:model="activo" class="rounded border-gray-300 dark:border-cerberus-steel"> Activo
                        </label>
                    </div>

                    <x-form.textarea label="Observaciones" wire:model="observaciones" rows="2" placeholder="N.º de contrato, soporte del proveedor, ubicación del módem..." />
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="guardar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="guardar" class="material-icons text-sm">save</span>
                        <span wire:loading wire:target="guardar" class="material-icons text-sm animate-spin">refresh</span>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
