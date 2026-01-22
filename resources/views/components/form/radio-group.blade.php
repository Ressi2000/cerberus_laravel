@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
])

<div class="mt-4">
    @if ($label)
        <label class="block mb-2 text-sm font-medium text-cerberus-light">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach ($options as $value => $text)
            <label
                class="flex items-center gap-3 p-3 rounded-lg border border-cerberus-steel bg-cerberus-dark
                       hover:border-cerberus-accent transition cursor-pointer">

                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $value }}"
                    class="text-cerberus-primary focus:ring-cerberus-primary"
                    {{ old($name, $selected) == $value ? 'checked' : '' }}
                >

                <span class="text-cerberus-light">{{ $text }}</span>
            </label>
        @endforeach
    </div>

    @error($name)
        <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>
