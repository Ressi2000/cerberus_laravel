{{--
    resources/views/components/form/textarea.blade.php

    Componente de textarea consistente con x-form.input y x-form.select.
    Soporta wire:model, x-model y atributos arbitrarios via $attributes.

    Uso:
        <x-form.textarea
            label="Observaciones"
            wire:model="observaciones"
            placeholder="Escribe aquí..."
            rows="4"
        />

    Con hint:
        <x-form.textarea
            label="Notas"
            wire:model="notas"
            hint="Máximo 1000 caracteres."
        />
--}}

@props([
    'label'       => null,
    'hint'        => null,
    'rows'        => 4,
    'placeholder' => '',
    'name'        => null,
])

@php
    $inputName  = $name ?? $attributes->wire('model')->value();
    $hasError   = $errors->has($inputName);
@endphp

<div class="mb-4">

    {{-- Label --}}
    @if ($label)
        <label
            @if ($inputName) for="{{ $inputName }}" @endif
            class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
            {{ $label }}
        </label>
    @endif

    {{-- Textarea --}}
    <textarea
        @if ($inputName) id="{{ $inputName }}" name="{{ $inputName }}" @endif
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full bg-white dark:bg-cerberus-dark
                        border text-gray-900 dark:text-white
                        placeholder-gray-400 dark:placeholder-cerberus-accent/60
                        rounded-lg px-3 py-2 text-sm
                        focus:outline-none focus:ring-1
                        transition-colors duration-150 resize-y
                        ' . ($hasError
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500/30'
                            : 'border-gray-300 dark:border-cerberus-steel
                               focus:border-cerberus-primary dark:focus:border-cerberus-primary
                               focus:ring-cerberus-primary/30'),
        ]) }}
    ></textarea>

    {{-- Error --}}
    @if ($hasError)
        <p class="mt-1 text-xs text-red-500 dark:text-red-400 flex items-center gap-1">
            <span class="material-icons text-xs">error_outline</span>
            {{ $errors->first($inputName) }}
        </p>
    @endif

    {{-- Hint --}}
    @if ($hint && !$hasError)
        <p class="mt-1 text-xs text-gray-500 dark:text-cerberus-steel">
            {{ $hint }}
        </p>
    @endif

</div>