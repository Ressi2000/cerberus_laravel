@props([
    'name'        => null,
    'label'       => null,
    'options'     => [],
    'selected'    => null,
    'required'    => false,
    'disabled'    => false,
    'hint'        => null,
    'placeholder' => 'Seleccione...',
    'error'       => null,
])

@php
    $hasWireModel = $attributes->has('wire:model')
                 || $attributes->has('wire:model.live')
                 || $attributes->has('wire:model.blur')
                 || $attributes->has('wire:model.lazy');

    $fieldId = $name ?? 'field-' . uniqid();
@endphp

<div class="mb-4">

    @if ($label)
        <label for="{{ $fieldId }}" class="block text-sm font-medium
               text-gray-700 dark:text-cerberus-accent mb-1">
            {{ $label }}
            @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $fieldId }}"
            @if ($name && !$hasWireModel) name="{{ $name }}" @endif
            @if ($required) required  @endif
            @if ($disabled)  disabled @endif
            {{ $attributes->merge([
                'class' =>
                    'w-full rounded-lg px-4 py-2 text-sm transition appearance-none
                     bg-white dark:bg-cerberus-dark
                     border border-gray-300 dark:border-cerberus-steel
                     text-[#1E293B] dark:text-white
                     focus:outline-none focus:ring-2
                     focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                     dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                     disabled:opacity-50 disabled:cursor-not-allowed'
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

        {{-- Chevron --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <span class="material-icons text-gray-400 dark:text-cerberus-steel text-base">expand_more</span>
        </div>

        @if ($hint)
            <div class="absolute inset-y-0 right-7 flex items-center group">
                <span class="material-icons text-cerberus-accent text-sm cursor-help">info</span>
                <div class="absolute right-6 bottom-full mb-1 w-52
                            bg-cerberus-dark border border-cerberus-steel
                            text-cerberus-light text-xs rounded-lg px-3 py-2
                            hidden group-hover:block z-10 shadow-lg whitespace-normal">
                    {{ $hint }}
                </div>
            </div>
        @endif
    </div>

    {{-- Error modo clásico --}}
    @if (!$hasWireModel && $name)
        @error($name)
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <span class="material-icons text-xs">error</span> {{ $message }}
            </p>
        @enderror
    @endif

    {{-- Error modo Livewire --}}
    @if ($error)
        <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error</span> {{ $error }}
        </p>
    @endif

</div>
