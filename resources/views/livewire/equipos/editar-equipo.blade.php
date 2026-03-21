<div class="space-y-6 max-w-4xl mx-auto">

    {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-icons text-[#1E40AF] dark:text-cerberus-accent text-2xl">edit</span>
                <div>
                    <h2 class="text-xl font-bold text-[#1E293B] dark:text-white">
                        Editar equipo
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-cerberus-light">
                        Código: <span class="font-mono text-[#1E293B] dark:text-white">
                            {{ $this->equipo->codigo_interno }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- Badge categoría (solo lectura) --}}
            <span class="px-3 py-1 text-xs rounded-full font-medium
                         bg-cerberus-primary/20 border border-cerberus-primary/40
                         text-cerberus-accent">
                {{ $this->equipo->categoria->nombre }}
            </span>
        </div>
    </div>

    {{-- ── SECCIÓN 1: DATOS GENERALES ─────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6 shadow-sm">

        <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent
                   uppercase tracking-wide mb-4">
            Datos generales
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">

            {{-- Categoría: solo lectura, no puede cambiarse --}}
            <div class="md:col-span-2 mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                    Categoría
                </label>
                <div class="w-full rounded-lg px-4 py-2 text-sm
                            bg-gray-100 dark:bg-cerberus-dark/50
                            border border-gray-200 dark:border-cerberus-steel/50
                            text-gray-500 dark:text-cerberus-steel
                            flex items-center gap-2 cursor-not-allowed">
                    <span class="material-icons text-sm">lock</span>
                    {{ $this->equipo->categoria->nombre }}
                    <span class="ml-auto text-xs italic">No modificable</span>
                </div>
            </div>

            <x-form.input
                label="Serial"
                wire:model="serial"
                placeholder="Ej: SN123456789"
                hint="Número de serie del fabricante. Debe ser único en el sistema."
                :error="$errors->first('serial')"
            />

            <x-form.input
                label="Hostname / Nombre máquina"
                wire:model="nombre_maquina"
                placeholder="Ej: PC-RRHH-01"
                hint="Nombre de red del equipo en el dominio o red interna."
                :error="$errors->first('nombre_maquina')"
            />

            <x-form.select
                label="Estado"
                wire:model="estado_id"
                :options="$this->estados"
                required
                hint="Estado operativo actual del equipo. Cambiar el estado queda registrado en la auditoría."
                :error="$errors->first('estado_id')"
            />

            <x-form.select
                label="Ubicación"
                wire:model="ubicacion_id"
                :options="$this->ubicaciones"
                hint="Ubicación física donde se encuentra el equipo."
                :error="$errors->first('ubicacion_id')"
            />

            <x-form.input
                type="date"
                label="Fecha de adquisición"
                wire:model="fecha_adquisicion"
                hint="Fecha en que la empresa adquirió el equipo."
                :error="$errors->first('fecha_adquisicion')"
            />

            <x-form.input
                type="date"
                label="Fecha fin de garantía"
                wire:model="fecha_garantia_fin"
                hint="Fecha hasta la que el fabricante cubre el equipo. El sistema alertará cuando esté próxima a vencer."
                :error="$errors->first('fecha_garantia_fin')"
            />

            {{-- Observaciones — ancho completo --}}
            <div class="md:col-span-2 mb-4">
                <div class="flex items-center gap-2 mb-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent">
                        Observaciones
                    </label>
                    <div class="relative flex items-center group">
                        <span class="material-icons text-gray-400 dark:text-cerberus-steel text-[16px]
                                     cursor-help hover:text-[#1E40AF] dark:hover:text-cerberus-accent
                                     transition-colors select-none">
                            help_outline
                        </span>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                    w-60 px-3 py-2 rounded-lg shadow-lg
                                    bg-[#1E293B] dark:bg-cerberus-dark
                                    text-white text-xs leading-relaxed
                                    border border-gray-600 dark:border-cerberus-steel
                                    opacity-0 invisible pointer-events-none
                                    group-hover:opacity-100 group-hover:visible
                                    transition-all duration-200 z-50">
                            Notas técnicas: condición actual, reparaciones, accesorios, etc.
                            <span class="absolute top-full left-1/2 -translate-x-1/2
                                         border-4 border-transparent
                                         border-t-[#1E293B] dark:border-t-cerberus-dark"></span>
                        </div>
                    </div>
                </div>
                <textarea wire:model="observaciones"
                          rows="3"
                          placeholder="Notas técnicas, condición del equipo, reparaciones previas, etc."
                          class="w-full rounded-lg px-4 py-2 text-sm transition resize-none
                                 bg-white dark:bg-cerberus-dark
                                 border border-gray-300 dark:border-cerberus-steel
                                 text-[#1E293B] dark:text-white
                                 placeholder-gray-400 dark:placeholder-gray-500
                                 focus:outline-none focus:ring-2
                                 focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                 dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary">
                </textarea>
            </div>

        </div>
    </div>

    {{-- ── SECCIÓN 2: ATRIBUTOS EAV DINÁMICOS ─────────────────────────────── --}}
    @if (count($atributos) > 0)
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                    rounded-xl p-6 shadow-sm">

            <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent
                       uppercase tracking-wide mb-1">
                Características técnicas
            </h3>
            <p class="text-xs text-cerberus-light mb-4 flex items-center gap-1">
                <span class="material-icons text-sm text-cerberus-steel">history</span>
                Cualquier cambio generará un nuevo registro en el historial del equipo.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                @foreach ($atributos as $atributo)
                    <div wire:key="attr-{{ $atributo['id'] }}">

                        @switch($atributo['tipo'])

                            @case('boolean')
                                <div class="mb-4">
                                    <label class="block text-sm font-medium
                                                  text-gray-700 dark:text-cerberus-accent mb-1">
                                        {{ $atributo['nombre'] }}
                                        @if ($atributo['requerido'])
                                            <span class="text-red-400">*</span>
                                        @endif
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer mt-1">
                                        <div class="relative">
                                            <input type="checkbox"
                                                   wire:model="valores.{{ $atributo['id'] }}"
                                                   class="sr-only peer">
                                            <div class="w-10 h-5 bg-cerberus-dark border border-cerberus-steel
                                                        rounded-full peer-checked:bg-cerberus-primary
                                                        transition-colors duration-200"></div>
                                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full
                                                        peer-checked:translate-x-5
                                                        transition-transform duration-200"></div>
                                        </div>
                                        <span class="text-cerberus-light text-sm">
                                            {{ $valores[$atributo['id']] ? 'Sí' : 'No' }}
                                        </span>
                                    </label>
                                </div>
                                @break

                            @case('select')
                                <x-form.select
                                    :label="$atributo['nombre']"
                                    wire:model="valores.{{ $atributo['id'] }}"
                                    :options="$atributo['opciones']"
                                    :required="$atributo['requerido']"
                                    :error="$errors->first('valores.' . $atributo['id'])"
                                />
                                @break

                            @case('text')
                                <div class="mb-4">
                                    <label class="block text-sm font-medium
                                                  text-gray-700 dark:text-cerberus-accent mb-1">
                                        {{ $atributo['nombre'] }}
                                        @if ($atributo['requerido'])
                                            <span class="text-red-400">*</span>
                                        @endif
                                    </label>
                                    <textarea wire:model="valores.{{ $atributo['id'] }}"
                                              rows="2"
                                              class="w-full rounded-lg px-4 py-2 text-sm resize-none
                                                     bg-white dark:bg-cerberus-dark
                                                     border border-gray-300 dark:border-cerberus-steel
                                                     text-[#1E293B] dark:text-white
                                                     focus:outline-none focus:ring-2
                                                     focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                                     dark:focus:ring-cerberus-primary/30
                                                     @error('valores.'.$atributo['id']) border-red-500 @enderror">
                                    </textarea>
                                    @error('valores.' . $atributo['id'])
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                @break

                            @case('integer')
                            @case('decimal')
                                <x-form.input
                                    type="number"
                                    :label="$atributo['nombre']"
                                    wire:model="valores.{{ $atributo['id'] }}"
                                    :required="$atributo['requerido']"
                                    :step="$atributo['tipo'] === 'decimal' ? '0.01' : '1'"
                                    :error="$errors->first('valores.' . $atributo['id'])"
                                />
                                @break

                            @case('date')
                                <x-form.input
                                    type="date"
                                    :label="$atributo['nombre']"
                                    wire:model="valores.{{ $atributo['id'] }}"
                                    :required="$atributo['requerido']"
                                    :error="$errors->first('valores.' . $atributo['id'])"
                                />
                                @break

                            @default
                                <x-form.input
                                    :label="$atributo['nombre']"
                                    wire:model="valores.{{ $atributo['id'] }}"
                                    :required="$atributo['requerido']"
                                    :placeholder="'Ingresar ' . strtolower($atributo['nombre']) . '...'"
                                    :error="$errors->first('valores.' . $atributo['id'])"
                                />
                        @endswitch

                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── NOTA HISTORIAL ──────────────────────────────────────────────────── --}}
    <div class="bg-cerberus-primary/10 dark:bg-cerberus-primary/10
                border border-cerberus-primary/30 rounded-xl px-5 py-3
                flex items-start gap-3">
        <span class="material-icons text-cerberus-accent text-lg mt-0.5">history</span>
        <p class="text-cerberus-light text-sm">
            Los cambios en las características técnicas quedarán registrados en el
            <strong class="text-white">historial del equipo</strong>
            con fecha, hora y usuario responsable.
        </p>
    </div>

    {{-- ── BOTONES ─────────────────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 pb-6">
        <a href="{{ route('admin.equipos.index') }}"
           class="px-5 py-2 rounded-lg border border-gray-300 dark:border-cerberus-steel
                  text-gray-600 dark:text-cerberus-light
                  hover:bg-gray-100 dark:hover:bg-cerberus-steel/20
                  transition text-sm font-medium">
            Cancelar
        </a>
        <button wire:click="actualizar"
                wire:loading.attr="disabled"
                class="px-6 py-2 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white font-semibold
                       rounded-lg transition flex items-center gap-2 text-sm disabled:opacity-60">
            <span wire:loading.remove wire:target="actualizar"
                  class="material-icons text-base">save</span>
            <span wire:loading wire:target="actualizar"
                  class="material-icons text-base animate-spin">refresh</span>
            <span wire:loading.remove wire:target="actualizar">Guardar cambios</span>
            <span wire:loading      wire:target="actualizar">Actualizando...</span>
        </button>
    </div>

</div>