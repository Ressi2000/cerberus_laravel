@props(['name', 'label', 'options' => [], 'selected' => null, 'required' => false])

<div class="mb-4">
    <label class="block text-cerberus-accent mb-1">{{ $label }}</label>

    <select name="{{ $name }}" {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' =>
                'w-full bg-cerberus-dark border border-cerberus-steel text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-cerberus-primary outline-none',
        ]) }}>
        <option value="">{{ $attributes->get('placeholder') ?? 'Seleccione...' }}</option>

        @foreach ($options as $value => $text)
            <option value="{{ $value }}"
                @selected($value == old($name, $selected))>
                {{ $text }}
            </option>
        @endforeach
    </select>
    @error($name)
        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
