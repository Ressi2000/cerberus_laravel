<div class="space-y-6 max-w-4xl mx-auto">

    {{-- HEADER DEL FORMULARIO --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <div class="flex items-center gap-3 mb-1">
            <span class="material-icons text-cerberus-accent text-2xl">devices</span>
            <h2 class="text-xl font-bold text-white">Registrar nuevo equipo</h2>
        </div>
        <p class="text-cerberus-light text-sm ml-9">
            Completa los datos base y las características técnicas según la categoría seleccionada.
        </p>
    </div>

    {{-- SECCIÓN 1: DATOS BASE --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">

        <h3 class="text-white font-semibold text-base mb-5 flex items-center gap-2">
            <span class="material-icons text-cerberus-accent text-lg">info</span>
            Datos generales
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Categoría --}}
            <div class="md:col-span-2">
                <label class="block text-cerberus-accent text-sm mb-1">
                    Categoría <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="categoria_id"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition
                           @error('categoria_id') border-red-500 @enderror">
                    <option value="">Seleccione una categoría...</option>
                    @foreach($categorias as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('categoria_id')
                    <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                        <span class="material-icons text-xs">error</span> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Código Interno --}}
            <div>
                <label class="block text-cerberus-accent text-sm mb-1">
                    Código interno <span class="text-red-400">*</span>
                </label>
                <input type="text"
                    wire:model="codigo_interno"
                    placeholder="Ej: EQ-2024-001"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           placeholder-gray-500 focus:ring-2 focus:ring-cerberus-primary outline-none transition
                           @error('codigo_interno') border-red-500 @enderror">
                @error('codigo_interno')
                    <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                        <span class="material-icons text-xs">error</span> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Serial --}}
            <div>
                <label class="block text-cerberus-accent text-sm mb-1">Serial</label>
                <input type="text"
                    wire:model="serial"
                    placeholder="Número de serie del fabricante"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           placeholder-gray-500 focus:ring-2 focus:ring-cerberus-primary outline-none transition">
            </div>

            {{-- Nombre de máquina --}}
            <div>
                <label class="block text-cerberus-accent text-sm mb-1">Hostname / Nombre máquina</label>
                <input type="text"
                    wire:model="nombre_maquina"
                    placeholder="Ej: PC-RRHH-01"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           placeholder-gray-500 focus:ring-2 focus:ring-cerberus-primary outline-none transition">
            </div>

            {{-- Estado --}}
            <div>
                <label class="block text-cerberus-accent text-sm mb-1">
                    Estado <span class="text-red-400">*</span>
                </label>
                <select wire:model="estado_id"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition
                           @error('estado_id') border-red-500 @enderror">
                    <option value="">Seleccione...</option>
                    @foreach($estados as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('estado_id')
                    <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                        <span class="material-icons text-xs">error</span> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Ubicación --}}
            <div>
                <label class="block text-cerberus-accent text-sm mb-1">Ubicación</label>
                <select wire:model="ubicacion_id"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition">
                    <option value="">Sin asignar</option>
                    @foreach($ubicaciones as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Fecha adquisición --}}
            <div>
                <label class="block text-cerberus-accent text-sm mb-1">Fecha de adquisición</label>
                <input type="date"
                    wire:model="fecha_adquisicion"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition
                           @error('fecha_adquisicion') border-red-500 @enderror">
                @error('fecha_adquisicion')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fecha garantía --}}
            <div>
                <label class="block text-cerberus-accent text-sm mb-1">Fecha fin de garantía</label>
                <input type="date"
                    wire:model="fecha_garantia_fin"
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition
                           @error('fecha_garantia_fin') border-red-500 @enderror">
                @error('fecha_garantia_fin')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Observaciones --}}
            <div class="md:col-span-2">
                <label class="block text-cerberus-accent text-sm mb-1">Observaciones</label>
                <textarea wire:model="observaciones"
                    rows="3"
                    placeholder="Notas técnicas, condición del equipo, etc."
                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                           placeholder-gray-500 focus:ring-2 focus:ring-cerberus-primary outline-none transition resize-none">
                </textarea>
            </div>

        </div>
    </div>

    {{-- SECCIÓN 2: ATRIBUTOS DINÁMICOS EAV --}}
    @if(count($atributos) > 0)
        <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6"
             wire:key="atributos-{{ $categoria_id }}">

            <h3 class="text-white font-semibold text-base mb-1 flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-lg">tune</span>
                Características técnicas
            </h3>
            <p class="text-cerberus-light text-xs mb-5 ml-7">
                Campos específicos de la categoría seleccionada.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                @foreach($atributos as $atributo)
                    <div wire:key="attr-{{ $atributo['id'] }}">

                        <label class="block text-cerberus-accent text-sm mb-1">
                            {{ $atributo['nombre'] }}
                            @if($atributo['requerido'])
                                <span class="text-red-400">*</span>
                            @endif
                        </label>

                        @switch($atributo['tipo'])

                            @case('boolean')
                                <label class="flex items-center gap-3 cursor-pointer mt-2">
                                    <div class="relative">
                                        <input type="checkbox"
                                            wire:model="valores.{{ $atributo['id'] }}"
                                            class="sr-only peer">
                                        <div class="w-10 h-5 bg-cerberus-dark border border-cerberus-steel rounded-full
                                                    peer-checked:bg-cerberus-primary transition-colors duration-200"></div>
                                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full
                                                    peer-checked:translate-x-5 transition-transform duration-200"></div>
                                    </div>
                                    <span class="text-cerberus-light text-sm">
                                        {{ $valores[$atributo['id']] ? 'Sí' : 'No' }}
                                    </span>
                                </label>
                                @break

                            @case('date')
                                <input type="date"
                                    wire:model="valores.{{ $atributo['id'] }}"
                                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                                           focus:ring-2 focus:ring-cerberus-primary outline-none transition
                                           @error('valores.'.$atributo['id']) border-red-500 @enderror">
                                @break

                            @case('text')
                                <textarea wire:model="valores.{{ $atributo['id'] }}"
                                    rows="2"
                                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                                           focus:ring-2 focus:ring-cerberus-primary outline-none transition resize-none
                                           @error('valores.'.$atributo['id']) border-red-500 @enderror">
                                </textarea>
                                @break

                            @case('integer')
                            @case('decimal')
                                <input type="number"
                                    wire:model="valores.{{ $atributo['id'] }}"
                                    step="{{ $atributo['tipo'] === 'decimal' ? '0.01' : '1' }}"
                                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                                           focus:ring-2 focus:ring-cerberus-primary outline-none transition
                                           @error('valores.'.$atributo['id']) border-red-500 @enderror">
                                @break

                            @default
                                <input type="text"
                                    wire:model="valores.{{ $atributo['id'] }}"
                                    class="w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                                           focus:ring-2 focus:ring-cerberus-primary outline-none transition
                                           @error('valores.'.$atributo['id']) border-red-500 @enderror">
                        @endswitch

                        @error('valores.'.$atributo['id'])
                            <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
                                <span class="material-icons text-xs">error</span> {{ $message }}
                            </p>
                        @enderror

                    </div>
                @endforeach

            </div>
        </div>
    @elseif($categoria_id)
        {{-- Categoría seleccionada pero sin atributos --}}
        <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-6 text-center">
            <span class="material-icons text-cerberus-steel text-4xl mb-2 block">tune</span>
            <p class="text-cerberus-light text-sm">
                Esta categoría no tiene características técnicas configuradas.
            </p>
        </div>
    @endif

    {{-- BOTONES --}}
    <div class="flex justify-end gap-3 pb-6">
        <a href="{{ route('admin.equipos.index') }}"
            class="px-5 py-2 rounded-lg border border-cerberus-steel text-cerberus-light
                   hover:bg-cerberus-steel/20 transition text-sm font-medium">
            Cancelar
        </a>
        <button wire:click="guardar"
                wire:loading.attr="disabled"
                class="px-6 py-2 bg-cerberus-primary hover:bg-cerberus-hover text-white font-semibold
                       rounded-lg transition flex items-center gap-2 text-sm disabled:opacity-60">
            <span wire:loading.remove wire:target="guardar"
                  class="material-icons text-base">save</span>
            <span wire:loading wire:target="guardar"
                  class="material-icons text-base animate-spin">refresh</span>
            <span wire:loading.remove wire:target="guardar">Registrar equipo</span>
            <span wire:loading wire:target="guardar">Guardando...</span>
        </button>
    </div>

</div>