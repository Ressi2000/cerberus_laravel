@props([
    'name',
    'label',
    'type'     => 'text',
    'required' => false,
    'value'    => null,
    'readonly' => false,
    'hint'     => null,
])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-cerberus-accent text-sm mb-1">
        {{ $label }}
        @if($required)
            <span class="text-red-400">*</span>
        @endif
    </label>

    <div class="relative">
        <input
            id="{{ $name }}"
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            {{ $required ? 'required' : '' }}
            {{ $readonly  ? 'readonly' : '' }}
            {{ $attributes->merge([
                'class' =>
                    'w-full bg-cerberus-dark border border-cerberus-steel rounded-lg px-4 py-2 text-white
                     placeholder-gray-500 focus:ring-2 focus:ring-cerberus-primary focus:border-cerberus-primary
                     outline-none transition-colors duration-150
                     ' . ($readonly ? 'opacity-60 cursor-not-allowed' : '')
            ]) }}
        >

        @if($hint)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 group">
                <span class="material-icons text-cerberus-accent text-sm cursor-help">info</span>
                <div class="absolute right-8 bottom-full mb-1 w-48 bg-cerberus-dark border border-cerberus-steel
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
