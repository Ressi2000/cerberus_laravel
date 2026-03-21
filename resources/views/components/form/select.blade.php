@props([
    'name'        => null,
    'label'       => null,
    'options'     => [],
    'selected'    => null,
    'required'    => false,
    'disabled'    => false,
    'hint'        => null,       // Texto de ayuda que aparece al hacer hover en el ícono (?)
    'placeholder' => 'Seleccione...',
    'error'       => null,
])

@php
    $hasWireModel = $attributes->has('wire:model')
                 || $attributes->has('wire:model.live')
                 || $attributes->has('wire:model.blur')
                 || $attributes->has('wire:model.lazy');

    $fieldId = $name ?? 'field-' . uniqid();
    $hintId  = $fieldId . '-hint';
    $errorId = $fieldId . '-error';
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

            @if ($hint)
                <div class="relative flex items-center group" aria-describedby="{{ $hintId }}">
                    <span class="material-icons text-gray-400 dark:text-cerberus-steel
                                 hover:text-[#1E40AF] dark:hover:text-cerberus-accent
                                 text-[16px] cursor-help transition-colors duration-150
                                 select-none">
                        help_outline
                    </span>

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
                        <span class="absolute top-full left-1/2 -translate-x-1/2
                                     border-4 border-transparent
                                     border-t-[#1E293B] dark:border-t-cerberus-dark">
                        </span>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ── SELECT ─────────────────────────────────────────────────────────── --}}
    <div class="relative">
        <select
            id="{{ $fieldId }}"
            @if ($name && !$hasWireModel) name="{{ $name }}" @endif
            @if ($required) required  @endif
            @if ($disabled) disabled  @endif
            @if ($hint)     aria-describedby="{{ $hintId }}" @endif
            {{ $attributes->merge([
                'class' =>
                    'w-full rounded-lg px-4 py-2 pr-9 text-sm transition appearance-none
                     bg-white dark:bg-cerberus-dark
                     border border-gray-300 dark:border-cerberus-steel
                     text-[#1E293B] dark:text-white
                     focus:outline-none focus:ring-2
                     focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                     dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                     disabled:opacity-50 disabled:cursor-not-allowed'
                     . ($error || (!$hasWireModel && $errors->has($name ?? ''))
                         ? ' border-red-400 dark:border-red-500'
                         : '')
            ]) }}
        >
            <option value="">{{ $placeholder }}</option>

            @foreach ($options as $optValue => $optLabel)
                <option
                    value="{{ $optValue }}"
                    @if (!$hasWireModel && old($name, $selected) == $optValue) selected @endif
                >
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>

        {{-- Chevron decorativo --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <span class="material-icons text-gray-400 dark:text-cerberus-steel text-base">
                expand_more
            </span>
        </div>
    </div>

    {{-- ── ERROR ──────────────────────────────────────────────────────────── --}}
    @if (!$hasWireModel && $name)
        @error($name)
            <p id="{{ $errorId }}" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <span class="material-icons text-xs">error_outline</span>
                {{ $message }}
            </p>
        @enderror
    @endif

    @if ($error)
        <p id="{{ $errorId }}" class="text-red-500 text-xs mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error_outline</span>
            {{ $error }}
        </p>
    @endif

</div>