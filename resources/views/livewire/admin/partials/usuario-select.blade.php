{{--
    Partial: select de usuario
    Variables: $field, $label, $options (collection/array), $required=false,
               $placeholder='Seleccione...', $disabled=false
--}}
<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
        {{ $label }}
        @if($required ?? false)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <select
        wire:model.live="{{ $field }}"
        {{ ($disabled ?? false) ? 'disabled' : '' }}
        class="w-full rounded-lg px-4 py-2 text-sm transition
               bg-white dark:bg-cerberus-dark
               border border-gray-300 dark:border-cerberus-steel
               text-[#1E293B] dark:text-white
               focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
               dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
               disabled:opacity-50 disabled:cursor-not-allowed
               @error($field) border-red-400 dark:border-red-500 @enderror">
        <option value="">{{ $placeholder ?? 'Seleccione...' }}</option>
        @foreach ($options ?? [] as $id => $nombre)
            <option value="{{ $id }}" wire:key="opt-{{ $field }}-{{ $id }}">
                {{ $nombre }}
            </option>
        @endforeach
    </select>
    @error($field)
        <p class="text-xs text-red-500 mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error</span>
            {{ $message }}
        </p>
    @enderror
</div>
