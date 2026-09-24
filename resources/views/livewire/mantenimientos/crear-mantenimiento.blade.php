<div class="space-y-5">

    @error('general')
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg
                    bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50
                    text-red-700 dark:text-red-300 text-sm">
            <span class="material-icons text-base flex-shrink-0">error_outline</span>
            {{ $message }}
        </div>
    @enderror

    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6 space-y-5">

        {{-- Tipo --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-2">
                Tipo de intervención <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" wire:click="$set('tipo', 'Correctivo')"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg border text-left transition
                           {{ $tipo === 'Correctivo'
                               ? 'border-cerberus-primary bg-cerberus-primary/10 text-cerberus-primary dark:text-cerberus-accent'
                               : 'border-gray-200 dark:border-cerberus-steel text-gray-600 dark:text-cerberus-light hover:bg-gray-50 dark:hover:bg-cerberus-dark/40' }}">
                    <span class="material-icons">construction</span>
                    <span>
                        <span class="block font-medium text-sm">Reparación</span>
                        <span class="block text-xs opacity-70">El equipo ya falló</span>
                    </span>
                </button>
                <button type="button" wire:click="$set('tipo', 'Preventivo')"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg border text-left transition
                           {{ $tipo === 'Preventivo'
                               ? 'border-cerberus-primary bg-cerberus-primary/10 text-cerberus-primary dark:text-cerberus-accent'
                               : 'border-gray-200 dark:border-cerberus-steel text-gray-600 dark:text-cerberus-light hover:bg-gray-50 dark:hover:bg-cerberus-dark/40' }}">
                    <span class="material-icons">build</span>
                    <span>
                        <span class="block font-medium text-sm">Mantenimiento</span>
                        <span class="block text-xs opacity-70">Revisión preventiva, programada</span>
                    </span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @if (Auth::user()->hasRole('Administrador'))
                <x-form.select
                    label="Empresa"
                    placeholder="Selecciona una empresa"
                    :options="$this->empresasOpciones"
                    wire:model.live="empresa_id"
                    :error="$errors->first('empresa_id')"
                    required
                />
            @endif

            {{-- wire:key fuerza a Livewire a reemplazar (no solo morfear) este bloque
                 cuando cambia la empresa: el select "searchable" inicializa sus
                 opciones una sola vez dentro de x-data, así que sin esto seguiría
                 mostrando la lista de equipos de la empresa anterior. --}}
            <div wire:key="equipo-select-{{ $empresa_id ?: 'sin-empresa' }}">
                <x-form.select
                    searchable
                    label="Equipo"
                    placeholder="{{ $empresa_id ? 'Selecciona un equipo' : 'Primero selecciona la empresa' }}"
                    :options="$this->equiposOpciones"
                    wire:model="equipo_id"
                    :error="$errors->first('equipo_id')"
                    :disabled="! $empresa_id"
                    hint="Solo se muestran equipos activos sin un mantenimiento/reparación abierto."
                    required
                />
            </div>
        </div>

        @if ($tipo === 'Correctivo')
            <x-form.textarea
                label="Falla reportada"
                wire:model="falla_reportada"
                rows="3"
                placeholder="Síntoma inicial: qué falla, cómo se detectó..."
                :error="$errors->first('falla_reportada')"
                required
            />

            <div class="flex items-center gap-3">
                <button type="button" wire:click="$toggle('en_garantia')" role="switch"
                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                           border-2 border-transparent transition-colors duration-200
                           {{ $en_garantia ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                 shadow ring-0 transition duration-200
                                 {{ $en_garantia ? 'translate-x-4' : 'translate-x-0' }}"></span>
                </button>
                <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">Aplica garantía del fabricante</span>
            </div>
        @else
            <x-form.textarea
                label="Descripción"
                wire:model="descripcion"
                rows="3"
                placeholder="Qué se va a revisar/hacer en este mantenimiento preventivo..."
                :error="$errors->first('descripcion')"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form.input
                    label="Frecuencia"
                    type="number"
                    wire:model="frecuencia_meses"
                    placeholder="Ej: 6"
                    suffix="meses"
                    hint="Cada cuánto se repite este mantenimiento."
                    :error="$errors->first('frecuencia_meses')"
                />
                <x-form.input
                    label="Próximo mantenimiento programado"
                    type="date"
                    wire:model="proxima_fecha_programada"
                    :error="$errors->first('proxima_fecha_programada')"
                />
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-form.input
                label="Fecha de inicio"
                type="date"
                wire:model="fecha_inicio"
                :error="$errors->first('fecha_inicio')"
                required
            />
            <x-form.input
                label="Fecha estimada de finalización"
                type="date"
                wire:model="fecha_fin_estimada"
                :error="$errors->first('fecha_fin_estimada')"
            />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-form.select
                searchable
                label="Responsable interno"
                placeholder="Sin asignar"
                :options="$this->responsablesOpciones"
                wire:model="responsable_id"
                :error="$errors->first('responsable_id')"
            />
            <x-form.input
                label="Proveedor externo"
                wire:model="proveedor_externo"
                placeholder="Nombre del taller/proveedor, si aplica"
                :error="$errors->first('proveedor_externo')"
            />
        </div>

    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.mantenimientos.index') }}"
            class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                   text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
            Cancelar
        </a>
        <button wire:click="guardar" wire:loading.attr="disabled"
            class="px-5 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A]
                   text-white transition flex items-center gap-2 disabled:opacity-60">
            <span wire:loading.remove wire:target="guardar" class="material-icons text-sm">save</span>
            <span wire:loading wire:target="guardar" class="material-icons text-sm animate-spin">refresh</span>
            <span wire:loading.remove wire:target="guardar">Registrar</span>
            <span wire:loading wire:target="guardar">Guardando...</span>
        </button>
    </div>

</div>
