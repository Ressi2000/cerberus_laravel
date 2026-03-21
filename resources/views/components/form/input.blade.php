@props([
    'name'        => null,
    'label'       => null,
    'type'        => 'text',
    'required'    => false,
    'value'       => null,
    'readonly'    => false,
    'disabled'    => false,
    'hint'        => null,       // Texto de ayuda que aparece al hacer hover en el ícono (?)
    'placeholder' => '',
    'error'       => null,
])

@php
    /*
     * Detectar si el componente recibe wire:model (en cualquier variante).
     * Cuando hay wire:model, NO ponemos value ni name en el input directamente,
     * porque Livewire los maneja solo.
     */
    $hasWireModel = $attributes->has('wire:model')
                 || $attributes->has('wire:model.live')
                 || $attributes->has('wire:model.blur')
                 || $attributes->has('wire:model.lazy');

    // ID único para accesibilidad (aria-describedby)
    $fieldId   = $name ?? 'field-' . uniqid();
    $hintId    = $fieldId . '-hint';
    $errorId   = $fieldId . '-error';
@endphp

<div class="mb-4">

    {{-- ── LABEL ──────────────────────────────────────────────────────────── --}}
    @if ($label)
        <div class="flex items-center gap-1.5 mb-1">
            <label for="{{ $fieldId }}"
                   class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500 ml-0.5">*</span>
                @endif
            </label>

            {{-- Ícono (?) con tooltip — solo si se pasó hint --}}
            @if ($hint)
                <div class="relative flex items-center group" aria-describedby="{{ $hintId }}">

                    {{-- Ícono interrogación --}}
                    <span class="material-icons text-gray-400 dark:text-cerberus-steel
                                 hover:text-[#1E40AF] dark:hover:text-cerberus-accent
                                 text-[16px] cursor-help transition-colors duration-150
                                 select-none">
                        help_outline
                    </span>

                    {{--
                        Tooltip:
                        - Aparece arriba del ícono (bottom-full)
                        - Se muestra con group-hover
                        - Ancho fijo para que no se deforme
                        - z-50 para estar sobre otros elementos
                        - Triángulo decorativo abajo
                    --}}
                    <div id="{{ $hintId }}"
                         role="tooltip"
                         class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                w-56 px-3 py-2 rounded-lg shadow-lg
                                bg-[#1E293B] dark:bg-cerberus-dark
                                text-white text-xs leading-relaxed
                                border border-gray-600 dark:border-cerberus-steel
                                opacity-0 invisible pointer-events-none
                                group-hover:opacity-100 group-hover:visible
                                transition-all duration-200 z-50
                                whitespace-normal">
                        {{ $hint }}

                        {{-- Triángulo apuntando hacia abajo --}}
                        <span class="absolute top-full left-1/2 -translate-x-1/2
                                     border-4 border-transparent
                                     border-t-[#1E293B] dark:border-t-cerberus-dark">
                        </span>
                    </div>

                </div>
            @endif
        </div>
    @endif

    {{-- ── INPUT ──────────────────────────────────────────────────────────── --}}
    <div class="relative">
        <input
            id="{{ $fieldId }}"
            @if ($name && !$hasWireModel) name="{{ $name }}" @endif
            type="{{ $type }}"
            @if (!$hasWireModel && $value !== null) value="{{ old($name, $value) }}" @endif
            placeholder="{{ $placeholder }}"
            @if ($required)  required  @endif
            @if ($readonly)  readonly  @endif
            @if ($disabled)  disabled  @endif
            @if ($hint)      aria-describedby="{{ $hintId }}"  @endif
            {{ $attributes->merge([
                'class' =>
                    'w-full rounded-lg px-4 py-2 text-sm transition
                     bg-white dark:bg-cerberus-dark
                     border border-gray-300 dark:border-cerberus-steel
                     text-[#1E293B] dark:text-white
                     placeholder-gray-400 dark:placeholder-gray-500
                     focus:outline-none focus:ring-2
                     focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                     dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                     read-only:bg-gray-50 read-only:dark:bg-cerberus-dark/60 read-only:cursor-default
                     disabled:opacity-50 disabled:cursor-not-allowed'
                     . ($error || (!$hasWireModel && $errors->has($name ?? ''))
                         ? ' border-red-400 dark:border-red-500 focus:ring-red-400/30 focus:border-red-400'
                         : '')
            ]) }}
        >
    </div>

    {{-- ── ERROR ──────────────────────────────────────────────────────────── --}}

    {{-- Error modo clásico (formularios sin Livewire) --}}
    @if (!$hasWireModel && $name)
        @error($name)
            <p id="{{ $errorId }}" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <span class="material-icons text-xs">error_outline</span>
                {{ $message }}
            </p>
        @enderror
    @endif

    {{-- Error modo Livewire (pasado con :error="$errors->first('campo')") --}}
    @if ($error)
        <p id="{{ $errorId }}" class="text-red-500 text-xs mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error_outline</span>
            {{ $error }}
        </p>
    @endif

</div>