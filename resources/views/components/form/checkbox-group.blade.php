@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => [],
    'columns' => 'grid-cols-1 md:grid-cols-2',
])

<div class="mt-4 mb-4">
    @if ($label)
        <label class="block mb-2 text-sm font-medium text-cerberus-light">
            {{ $label }}
        </label>
    @endif

    <div class="grid {{ $columns }} gap-3">
        @foreach ($options as $value => $text)
            <label
                class="flex items-center gap-3 p-3 rounded-lg border border-cerberus-steel bg-cerberus-dark
                       hover:border-cerberus-accent transition cursor-pointer">

                <input
                    type="checkbox"
                    name="{{ $name }}[]"
                    value="{{ $value }}"
                    class="rounded text-cerberus-primary focus:ring-cerberus-primary"
                    {{ in_array($value, old($name, $selected)) ? 'checked' : '' }}
                >

                <span class="text-cerberus-light">{{ $text }}</span>
            </label>
        @endforeach
    </div>

    @error($name)
        <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>
