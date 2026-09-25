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
        </div>

        <div>
            <button type="button" wire:click="$set('buscarPorUsuario', {{ $buscarPorUsuario ? 'false' : 'true' }})"
                class="text-xs text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1 mb-2">
                <span class="material-icons text-sm">{{ $buscarPorUsuario ? 'devices' : 'person_search' }}</span>
                {{ $buscarPorUsuario ? 'Buscar por código de equipo' : 'No recuerdo el equipo — buscar por la persona que lo tiene' }}
            </button>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if ($buscarPorUsuario)
                    <div wire:key="usuario-select-{{ $empresa_id ?: 'sin-empresa' }}">
                        <x-form.select
                            searchable
                            label="Persona"
                            placeholder="{{ $empresa_id ? 'Selecciona una persona' : 'Primero selecciona la empresa' }}"
                            :options="$this->usuariosOpciones"
                            wire:model.live="usuario_id"
                            :disabled="! $empresa_id"
                            hint="Solo aparecen personas con al menos un equipo asignado actualmente."
                        />
                    </div>
                @endif

                {{-- wire:key fuerza a Livewire a reemplazar (no solo morfear) este bloque
                     cuando cambia la empresa/persona/modo: el select "searchable" inicializa
                     sus opciones una sola vez dentro de x-data, así que sin esto seguiría
                     mostrando la lista de equipos anterior. --}}
                <div wire:key="equipo-select-{{ $empresa_id ?: 'sin-empresa' }}-{{ $buscarPorUsuario ? 'u' . $usuario_id : 'todos' }}">
                    <x-form.select
                        searchable
                        label="Equipo"
                        :placeholder="! $empresa_id ? 'Primero selecciona la empresa' : ($buscarPorUsuario && ! $usuario_id ? 'Primero selecciona la persona' : 'Selecciona un equipo')"
                        :options="$this->equiposOpciones"
                        wire:model.live="equipo_id"
                        :error="$errors->first('equipo_id')"
                        :disabled="! $empresa_id || ($buscarPorUsuario && ! $usuario_id)"
                        hint="Solo se muestran equipos activos sin un mantenimiento/reparación abierto."
                        required
                    />
                </div>
            </div>
        </div>

        @if ($this->equipoSeleccionado?->fecha_garantia_fin?->isFuture())
            <div class="flex items-start gap-2 px-4 py-3 rounded-lg
                        bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/40
                        text-blue-700 dark:text-blue-300 text-sm">
                <span class="material-icons text-base flex-shrink-0">verified_user</span>
                <div class="flex-1">
                    Este equipo sigue en garantía hasta el {{ $this->equipoSeleccionado->fecha_garantia_fin->format('d/m/Y') }}.
                    Es solo informativo — decidí si corresponde marcarlo.
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" wire:click="$toggle('en_garantia')" role="switch"
                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                           border-2 border-transparent transition-colors duration-200
                           {{ $en_garantia ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                 shadow ring-0 transition duration-200
                                 {{ $en_garantia ? 'translate-x-4' : 'translate-x-0' }}"></span>
                </button>
                <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">Marcar como cubierto por garantía</span>
            </div>
        @endif

        {{-- Foto "Antes" — evidencia obligatoria del estado inicial del equipo --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                Foto "Antes" <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-4">
                @if ($fotoAntes)
                    <img src="{{ $fotoAntes->temporaryUrl() }}" class="w-16 h-16 rounded-lg object-cover border border-gray-200 dark:border-cerberus-steel">
                @endif

                <label class="cursor-pointer flex items-center gap-2 px-4 py-2 rounded-lg text-sm
                              bg-gray-100 dark:bg-cerberus-dark
                              border border-gray-300 dark:border-cerberus-steel
                              text-gray-700 dark:text-cerberus-light
                              hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                    <span class="material-icons text-base">upload</span>
                    {{ $fotoAntes ? 'Cambiar foto' : 'Subir foto' }}
                    <input type="file" wire:model="fotoAntes" class="hidden" accept="image/*">
                </label>

                <div wire:loading wire:target="fotoAntes" class="text-xs text-gray-400 flex items-center gap-1">
                    <span class="material-icons text-sm animate-spin">refresh</span>
                    Subiendo...
                </div>
            </div>
            <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-1">
                Evidencia del estado del equipo antes de intervenirlo. JPG, PNG · máx. 5MB.
            </p>
            @error('fotoAntes')
                <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                    <span class="material-icons text-xs">error_outline</span> {{ $message }}
                </p>
            @enderror
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
                    hint="Cada cuánto se repite este mantenimiento. Si querés que se repita solo, armá un Plan de mantenimiento desde Cronograma en vez de llenar esto acá."
                    :error="$errors->first('frecuencia_meses')"
                />
                <x-form.input
                    label="Próximo mantenimiento programado"
                    type="date"
                    wire:model="proxima_fecha_programada"
                    :error="$errors->first('proxima_fecha_programada')"
                />
            </div>

            {{-- Checklist --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-2">
                    Checklist de la revisión
                </label>
                <div class="space-y-1.5">
                    @foreach ($checklist as $i => $item)
                        <div wire:key="checklist-{{ $i }}" class="flex items-center gap-2">
                            <button type="button" wire:click="$set('checklist.{{ $i }}.incluir', {{ $item['incluir'] ? 'false' : 'true' }})"
                                class="flex-shrink-0 w-5 h-5 rounded border flex items-center justify-center transition
                                       {{ $item['incluir']
                                           ? 'bg-cerberus-primary border-cerberus-primary text-white'
                                           : 'border-gray-300 dark:border-cerberus-steel' }}">
                                @if ($item['incluir'])
                                    <span class="material-icons text-xs">check</span>
                                @endif
                            </button>
                            <span class="text-sm flex-1 {{ $item['incluir'] ? 'text-gray-700 dark:text-cerberus-light' : 'text-gray-400 dark:text-cerberus-steel line-through' }}">
                                {{ $item['tarea'] }}
                            </span>
                            <button type="button" wire:click="quitarTareaChecklist({{ $i }})"
                                class="text-gray-400 hover:text-red-500 transition">
                                <span class="material-icons text-sm">close</span>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-2 mt-3">
                    <input type="text" wire:model="nuevaTareaChecklist" wire:keydown.enter.prevent="agregarTareaChecklist"
                        placeholder="Agregar otra tarea..."
                        class="flex-1 rounded-lg px-3 py-1.5 text-sm
                               bg-white dark:bg-cerberus-dark
                               border border-gray-300 dark:border-cerberus-steel
                               text-gray-900 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30">
                    <button type="button" wire:click="agregarTareaChecklist"
                        class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">
                        <span class="material-icons text-sm">add</span>
                    </button>
                </div>
                <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-1">
                    Se van tildando a medida que se completan, desde el detalle del caso.
                </p>
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
