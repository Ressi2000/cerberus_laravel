@props([
    'name',
    'label',
    'options'   => [],
    'selected'  => null,
    'required'  => false,
    'disabled'  => false,
    'hint'      => null,
    'placeholder' => 'Seleccione...',
])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-cerberus-accent text-sm mb-1">
        {{ $label }}
        @if($required)
            <span class="text-red-400">*</span>
        @endif
    </label>

    <div class="relative">
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge([
                'class' =>
                    'w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2
                     focus:ring-2 focus:ring-cerberus-primary focus:border-cerberus-primary outline-none
                     transition-colors duration-150
                     ' . ($disabled ? 'opacity-60 cursor-not-allowed' : '')
            ]) }}>

            <option value="">{{ $placeholder }}</option>

            @foreach ($options as $value => $text)
                <option value="{{ $value }}"
                    @selected($value == old($name, $selected))>
                    {{ $text }}
                </option>
            @endforeach
        </select>

        @if($hint)
            <div class="absolute inset-y-0 right-8 flex items-center group">
                <span class="material-icons text-cerberus-accent text-sm cursor-help">info</span>
                <div class="absolute right-6 bottom-full mb-1 w-48 bg-cerberus-dark border border-cerberus-steel
                            text-cerberus-light text-xs rounded-lg px-3 py-2 hidden group-hover:block z-10 shadow-lg">
                    {{ $hint }}
                </div>
            </div>
        @endif
    </div>

    @error($name)
        <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error</span>
            {{ $message }}
        </p>
    @enderror
</div>
