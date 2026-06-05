{{--
    Partial: _eav-campo-group.blade.php
    Renderiza un atributo de tipo 'group' (multi-instancia con sub-campos).

    Variables esperadas:
      $atributo    — array del atributo: {id, nombre, tipo, requerido, sub_campos:[...]}
      $modo        — 'crear' | 'editar'
--}}
@php
    $atributoId = $atributo['id'];
    $subCampos  = $atributo['sub_campos'] ?? [];
    $instancias = $grupoInstancias[$atributoId] ?? [];
@endphp

<div class="md:col-span-2" wire:key="attr-group-{{ $atributoId }}">
    <div class="border border-gray-200 dark:border-cerberus-steel/50 rounded-xl overflow-hidden">

        {{-- Cabecera del grupo --}}
        <div class="flex items-center justify-between px-4 py-3
                    bg-gray-50 dark:bg-cerberus-dark/40
                    border-b border-gray-100 dark:border-cerberus-steel/30">
            <div>
                <p class="text-sm font-medium text-gray-800 dark:text-white">
                    {{ $atributo['nombre'] }}
                    @if ($atributo['requerido'])
                        <span class="text-red-400">*</span>
                    @endif
                </p>
                <p class="text-xs text-gray-500 dark:text-cerberus-light mt-0.5">
                    {{ count($instancias) }} instancia(s) registrada(s)
                </p>
            </div>
            <button wire:click="agregarInstanciaGrupo({{ $atributoId }})" type="button"
                class="flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg font-medium
                       text-[#1E40AF] dark:text-cerberus-accent
                       bg-[#1E40AF]/10 dark:bg-cerberus-primary/10
                       hover:bg-[#1E40AF]/20 dark:hover:bg-cerberus-primary/20 transition">
                <span class="material-icons text-sm">add</span>
                Agregar {{ $atributo['nombre'] }}
            </button>
        </div>

        {{-- Lista de instancias --}}
        @if (count($instancias) > 0)
            <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/20">
                @foreach ($instancias as $idx => $instancia)
                    <div class="p-4" wire:key="inst-{{ $atributoId }}-{{ $idx }}">

                        {{-- Encabezado de instancia --}}
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 dark:text-cerberus-light uppercase tracking-wide">
                                {{ $atributo['nombre'] }} #{{ $idx + 1 }}
                            </p>
                            <button wire:click="eliminarInstanciaGrupo({{ $atributoId }}, {{ $idx }})" type="button"
                                class="flex items-center gap-1 text-xs px-2 py-1 rounded-lg
                                       text-red-500 dark:text-red-400
                                       hover:bg-red-50 dark:hover:bg-red-500/10 transition">
                                <span class="material-icons text-sm">delete</span>
                                Eliminar
                            </button>
                        </div>

                        {{-- Sub-campos de la instancia --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($subCampos as $sc)
                                <div wire:key="sc-{{ $sc['id'] }}-{{ $idx }}">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-cerberus-accent mb-1">
                                        {{ $sc['nombre'] }}
                                        @if ($sc['requerido']) <span class="text-red-400">*</span> @endif
                                    </label>

                                    @if ($sc['tipo'] === 'select')
                                        <select wire:model="grupoInstancias.{{ $atributoId }}.{{ $idx }}.{{ $sc['id'] }}"
                                            class="w-full rounded-lg px-3 py-1.5 text-sm
                                                   bg-white dark:bg-cerberus-dark
                                                   border border-gray-300 dark:border-cerberus-steel
                                                   text-gray-900 dark:text-white
                                                   focus:outline-none focus:ring-2
                                                   focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                                   dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary">
                                            <option value="">Seleccione...</option>
                                            @foreach ($sc['opciones'] ?? [] as $opcion)
                                                <option value="{{ $opcion }}">{{ $opcion }}</option>
                                            @endforeach
                                        </select>

                                    @elseif ($sc['tipo'] === 'boolean')
                                        <div class="flex items-center gap-4 h-[34px]">
                                            <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700 dark:text-white">
                                                <input type="radio"
                                                    wire:model="grupoInstancias.{{ $atributoId }}.{{ $idx }}.{{ $sc['id'] }}"
                                                    value="1"
                                                    class="text-cerberus-primary focus:ring-cerberus-primary">
                                                Sí
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700 dark:text-white">
                                                <input type="radio"
                                                    wire:model="grupoInstancias.{{ $atributoId }}.{{ $idx }}.{{ $sc['id'] }}"
                                                    value="0"
                                                    class="text-cerberus-primary focus:ring-cerberus-primary">
                                                No
                                            </label>
                                        </div>

                                    @else
                                        @php
                                            $inputType = match ($sc['tipo']) {
                                                'integer', 'decimal' => 'number',
                                                'date'               => 'date',
                                                default              => 'text',
                                            };
                                        @endphp
                                        <input
                                            type="{{ $inputType }}"
                                            wire:model="grupoInstancias.{{ $atributoId }}.{{ $idx }}.{{ $sc['id'] }}"
                                            @if ($sc['tipo'] === 'decimal') step="0.01" @endif
                                            class="w-full rounded-lg px-3 py-1.5 text-sm
                                                   bg-white dark:bg-cerberus-dark
                                                   border border-gray-300 dark:border-cerberus-steel
                                                   text-gray-900 dark:text-white
                                                   placeholder-gray-400 dark:placeholder-gray-500
                                                   focus:outline-none focus:ring-2
                                                   focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                                   dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary">
                                    @endif
                                </div>
                            @endforeach
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <div class="py-10 text-center">
                <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel block mb-2">
                    add_circle_outline
                </span>
                <p class="text-sm text-gray-400 dark:text-cerberus-steel">
                    Pulsa «Agregar {{ $atributo['nombre'] }}» para registrar la primera instancia.
                </p>
            </div>
        @endif

        {{-- Error de validación del grupo --}}
        @error("grupoInstancias.{$atributoId}")
            <div class="px-4 py-2 border-t border-red-100 dark:border-red-500/20 bg-red-50 dark:bg-red-500/10">
                <p class="text-red-500 dark:text-red-400 text-xs flex items-center gap-1">
                    <span class="material-icons text-xs">error</span>
                    {{ $message }}
                </p>
            </div>
        @enderror

    </div>
</div>
