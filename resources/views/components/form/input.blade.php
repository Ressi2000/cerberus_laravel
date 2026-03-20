@props([
    'name'        => null,
    'label'       => null,
    'type'        => 'text',
    'required'    => false,
    'value'       => null,
    'readonly'    => false,
    'disabled'    => false,
    'hint'        => null,
    'placeholder' => '',
    'error'       => null,
])

@php
    /*
     * Detectar wire:model correctamente.
     * $attributes->has() funciona con directivas Livewire en Laravel 12.
     * Cubre: wire:model, wire:model.live, wire:model.blur, etc.
     */
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
        <input
            id="{{ $fieldId }}"
            @if ($name && !$hasWireModel) name="{{ $name }}" @endif
            type="{{ $type }}"
            @if (!$hasWireModel) value="{{ old($name, $value) }}" @endif
            placeholder="{{ $placeholder }}"
            @if ($required) required  @endif
            @if ($readonly)  readonly @endif
            @if ($disabled)  disabled @endif
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
                     disabled:opacity-50 disabled:cursor-not-allowed'
            ]) }}
        >

        @if ($hint)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 group">
                <span class="material-icons text-cerberus-accent text-sm cursor-help">info</span>
                <div class="absolute right-8 bottom-full mb-1 w-52
                            bg-cerberus-dark border border-cerberus-steel
                            text-cerberus-light text-xs rounded-lg px-3 py-2
                            hidden group-hover:block z-10 shadow-lg whitespace-normal">
                    {{ $hint }}
                </div>
            </div>
        @endif
    </div>

    {{-- Error modo clásico (detectado por $name) --}}
    @if (!$hasWireModel && $name)
        @error($name)
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <span class="material-icons text-xs">error</span> {{ $message }}
            </p>
        @enderror
    @endif

    {{-- Error modo Livewire (pasado con :error="$errors->first('campo')") --}}
    @if ($error)
        <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error</span> {{ $error }}
        </p>
    @endif

</div>
