@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'value' => null,
])

<div class="mb-4">
    <label class="block text-cerberus-accent mb-1">{{ $label }}</label>
    <input type="{{ $type }}" 
           name="{{ $name }}"
           value="{{ old($name, $value) }}"
           {{ $required ? 'required' : '' }}
           class="w-full bg-cerberus-dark border border-cerberus-steel rounded-lg px-4 py-2 text-white
                  focus:ring-2 focus:ring-cerberus-primary outline-none">
</div>
