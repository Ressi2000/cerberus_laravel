<div class="space-y-6 max-w-4xl mx-auto">

    {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
    <div
        class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="material-icons text-[#1E40AF] dark:text-cerberus-accent text-2xl">add_circle</span>
            <div>
                <h2 class="text-xl font-bold text-[#1E293B] dark:text-white">
                    Registrar nuevo equipo
                </h2>
                <p class="text-sm text-gray-500 dark:text-cerberus-light">
                    Completa los datos base y las características técnicas según la categoría.
                </p>
            </div>
        </div>
    </div>

    {{-- ── SECCIÓN 1: DATOS GENERALES ─────────────────────────────────────── --}}
    <div
        class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6 shadow-sm">

        <h3
            class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent
                   uppercase tracking-wide mb-4">
            Datos generales
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">

            {{-- Categoría — ancho completo --}}
            <div class="md:col-span-2">
                <x-form.select label="Categoría" wire:model.live="categoria_id" :options="$categorias" required
                    hint="Tipo de activo tecnológico. Al seleccionar la categoría se cargarán los campos técnicos específicos (RAM, procesador, etc.)."
                    :error="$errors->first('categoria_id')" />
            </div>

            <div>
                <label class="block text-cerberus-accent text-sm mb-1">Código interno</label>
                <div
                    class="w-full bg-cerberus-dark/50 border border-cerberus-steel/40 text-cerberus-light 
                rounded-lg px-4 py-2 flex items-center gap-2 text-sm">
                    <span class="material-icons text-base text-cerberus-steel">auto_awesome</span>
                    <span class="italic">Se asignará automáticamente al guardar (ej: EQ-00042)</span>
                </div>
            </div>

            <x-form.input label="Serial" wire:model="serial" placeholder="Ej: SN123456789"
                hint="Número de serie del fabricante. Se encuentra en la etiqueta del equipo o en el BIOS/Sistema."
                :error="$errors->first('serial')" />

            <x-form.input label="Hostname / Nombre máquina" wire:model.lazy="nombre_maquina"
                placeholder="Ej: WVETSD104"
                hint="Nombre de red único del equipo. Debe coincidir exactamente con el nombre registrado en el Active Directory. Si ya existe en el sistema, no podrá registrarse."
                :error="$errors->first('nombre_maquina')" />

            <x-form.select label="Estado" wire:model="estado_id" :options="$estados" required
                hint="Estado operativo actual. Al crear un equipo nuevo normalmente se registra como Disponible."
                :error="$errors->first('estado_id')" />

            <x-form.select label="Ubicación" wire:model="ubicacion_id" :options="$ubicaciones"
                hint="Sede o área donde se encuentra físicamente el equipo. Debe pertenecer a la empresa activa."
                :error="$errors->first('ubicacion_id')" />

            <x-form.input type="date" label="Fecha de adquisición" wire:model="fecha_adquisicion"
                hint="Fecha en que la empresa adquirió el equipo. Se usa para calcular la vida útil."
                :error="$errors->first('fecha_adquisicion')" />

            <x-form.input type="date" label="Fecha fin de garantía" wire:model="fecha_garantia_fin"
                hint="Fecha hasta la que el fabricante cubre el equipo. El sistema alertará cuando esté por vencer."
                :error="$errors->first('fecha_garantia_fin')" />

            {{-- Observaciones — ancho completo --}}
            <div class="md:col-span-2 mb-4">
                <div class="flex items-center gap-2 mb-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent">
                        Observaciones
                    </label>
                    {{-- Hint manual para textarea --}}
                    <div class="relative flex items-center group">
                        <span
                            class="material-icons text-gray-400 dark:text-cerberus-steel text-[16px]
                                     cursor-help hover:text-[#1E40AF] dark:hover:text-cerberus-accent
                                     transition-colors select-none">
                            help_outline
                        </span>
                        <div
                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                    w-60 px-3 py-2 rounded-lg shadow-lg
                                    bg-[#1E293B] dark:bg-cerberus-dark
                                    text-white text-xs leading-relaxed
                                    border border-gray-600 dark:border-cerberus-steel
                                    opacity-0 invisible pointer-events-none
                                    group-hover:opacity-100 group-hover:visible
                                    transition-all duration-200 z-50">
                            Notas técnicas adicionales: condición al ingresar, accesorios incluidos,
                            historial relevante, etc.
                            <span
                                class="absolute top-full left-1/2 -translate-x-1/2
                                         border-4 border-transparent
                                         border-t-[#1E293B] dark:border-t-cerberus-dark"></span>
                        </div>
                    </div>
                </div>
                <textarea wire:model="observaciones" rows="3"
                    placeholder="Notas técnicas, condición del equipo al ingresar, accesorios, etc."
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
                rounded-xl p-6 shadow-sm"
            wire:key="atributos-{{ $categoria_id }}">

            <h3
                class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent
                   uppercase tracking-wide mb-1">
                Características técnicas
            </h3>
            <p class="text-xs text-cerberus-light mb-4">
                Campos específicos de la categoría seleccionada.
                Los marcados con <span class="text-red-400">*</span> son obligatorios.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                @foreach ($atributos as $atributo)
                    {{-- ── Tipo FILE ─────────────────────────────────────────── --}}
                    @if ($atributo['tipo'] === 'file')
                        <div class="md:col-span-2">
                            @include('livewire.equipos.partials._eav-campo-file', [
                                'atributo' => $atributo,
                                'archivoActual' => null,
                                'modo' => 'crear',
                            ])
                        </div>

                        {{-- ── Tipo BOOLEAN ──────────────────────────────────────── --}}
                    @elseif ($atributo['tipo'] === 'boolean')
                        <div wire:key="attr-{{ $atributo['id'] }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-2">
                                {{ $atributo['nombre'] }}
                                @if ($atributo['requerido'])
                                    <span class="text-red-400">*</span>
                                @endif
                            </label>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="valores.{{ $atributo['id'] }}" value="1"
                                        class="text-cerberus-primary focus:ring-cerberus-primary">
                                    <span class="text-sm text-gray-700 dark:text-white">Sí</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="valores.{{ $atributo['id'] }}" value="0"
                                        class="text-cerberus-primary focus:ring-cerberus-primary">
                                    <span class="text-sm text-gray-700 dark:text-white">No</span>
                                </label>
                            </div>
                            @error('valores.' . $atributo['id'])
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ── Tipo SELECT ───────────────────────────────────────── --}}
                    @elseif ($atributo['tipo'] === 'select')
                        <div wire:key="attr-{{ $atributo['id'] }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                                {{ $atributo['nombre'] }}
                                @if ($atributo['requerido'])
                                    <span class="text-red-400">*</span>
                                @endif
                            </label>
                            <select wire:model="valores.{{ $atributo['id'] }}"
                                class="w-full rounded-lg px-4 py-2 text-sm transition
                                   bg-white dark:bg-cerberus-dark
                                   border border-gray-300 dark:border-cerberus-steel
                                   text-gray-900 dark:text-white
                                   focus:outline-none focus:ring-2
                                   focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                   dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                   @error('valores.' . $atributo['id']) border-red-400 @enderror">
                                <option value="">Seleccione...</option>
                                @foreach ($atributo['opciones'] as $opcion)
                                    <option value="{{ $opcion }}">{{ $opcion }}</option>
                                @endforeach
                            </select>
                            @error('valores.' . $atributo['id'])
                                <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                                    <span class="material-icons text-xs">error</span> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- ── Tipo DATE ─────────────────────────────────────────── --}}
                    @elseif ($atributo['tipo'] === 'date')
                        <div wire:key="attr-{{ $atributo['id'] }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                                {{ $atributo['nombre'] }}
                                @if ($atributo['requerido'])
                                    <span class="text-red-400">*</span>
                                @endif
                            </label>
                            <input type="date" wire:model="valores.{{ $atributo['id'] }}"
                                class="w-full rounded-lg px-4 py-2 text-sm transition
                                      bg-white dark:bg-cerberus-dark
                                      border border-gray-300 dark:border-cerberus-steel
                                      text-gray-900 dark:text-white
                                      focus:outline-none focus:ring-2
                                      focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                      dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                      @error('valores.' . $atributo['id']) border-red-400 @enderror">
                            @error('valores.' . $atributo['id'])
                                <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                                    <span class="material-icons text-xs">error</span> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- ── Tipo TEXT (textarea) ──────────────────────────────── --}}
                    @elseif ($atributo['tipo'] === 'text')
                        <div class="md:col-span-2" wire:key="attr-{{ $atributo['id'] }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                                {{ $atributo['nombre'] }}
                                @if ($atributo['requerido'])
                                    <span class="text-red-400">*</span>
                                @endif
                            </label>
                            <textarea wire:model="valores.{{ $atributo['id'] }}" rows="3"
                                class="w-full rounded-lg px-4 py-2 text-sm transition resize-none
                                         bg-white dark:bg-cerberus-dark
                                         border border-gray-300 dark:border-cerberus-steel
                                         text-gray-900 dark:text-white
                                         placeholder-gray-400 dark:placeholder-gray-500
                                         focus:outline-none focus:ring-2
                                         focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                         dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                         @error('valores.' . $atributo['id']) border-red-400 @enderror">
                        </textarea>
                            @error('valores.' . $atributo['id'])
                                <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                                    <span class="material-icons text-xs">error</span> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- ── Resto de tipos: string, integer, decimal ─────────── --}}
                    @else
                        <div wire:key="attr-{{ $atributo['id'] }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                                {{ $atributo['nombre'] }}
                                @if ($atributo['requerido'])
                                    <span class="text-red-400">*</span>
                                @endif
                            </label>
                            <input
                                type="{{ in_array($atributo['tipo'], ['integer', 'decimal']) ? 'number' : 'text' }}"
                                wire:model="valores.{{ $atributo['id'] }}"
                                @if ($atributo['tipo'] === 'decimal') step="0.01" @endif
                                class="w-full rounded-lg px-4 py-2 text-sm transition
                                      bg-white dark:bg-cerberus-dark
                                      border border-gray-300 dark:border-cerberus-steel
                                      text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2
                                      focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                      dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                      @error('valores.' . $atributo['id']) border-red-400 @enderror">
                            @error('valores.' . $atributo['id'])
                                <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                                    <span class="material-icons text-xs">error</span> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif
                @endforeach

            </div>
        </div>
    @elseif ($categoria_id)
        {{-- Categoría seleccionada pero sin atributos configurados --}}
        <div
            class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                    rounded-xl p-6 text-center shadow-sm">
            <span class="material-icons text-4xl block mb-2 text-cerberus-steel">tune</span>
            <p class="text-cerberus-light text-sm">
                Esta categoría no tiene características técnicas configuradas.
            </p>
        </div>
    @endif

    {{-- ── BOTONES ─────────────────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 pb-6">
        <a href="{{ route('admin.equipos.index') }}"
            class="px-5 py-2 rounded-lg border border-gray-300 dark:border-cerberus-steel
                  text-gray-600 dark:text-cerberus-light
                  hover:bg-gray-100 dark:hover:bg-cerberus-steel/20
                  transition text-sm font-medium">
            Cancelar
        </a>
        <button wire:click="guardar" wire:loading.attr="disabled"
            class="px-6 py-2 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white font-semibold
                       rounded-lg transition flex items-center gap-2 text-sm disabled:opacity-60">
            <span wire:loading.remove wire:target="guardar" class="material-icons text-base">save</span>
            <span wire:loading wire:target="guardar" class="material-icons text-base animate-spin">refresh</span>
            <span wire:loading.remove wire:target="guardar">Registrar equipo</span>
            <span wire:loading wire:target="guardar">Guardando...</span>
        </button>
    </div>

</div>
