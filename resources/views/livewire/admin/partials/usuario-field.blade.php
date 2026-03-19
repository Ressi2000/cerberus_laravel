{{--
    Partial: campo input de usuario
    Variables: $field, $label, $type='text', $required=false, $placeholder=''
--}}
<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
        {{ $label }}
        @if($required ?? false)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <input
        type="{{ $type ?? 'text' }}"
        wire:model="{{ $field }}"
        placeholder="{{ $placeholder ?? '' }}"
        class="w-full rounded-lg px-4 py-2 text-sm transition
               bg-white dark:bg-cerberus-dark
               border border-gray-300 dark:border-cerberus-steel
               text-[#1E293B] dark:text-white
               placeholder-gray-400 dark:placeholder-gray-500
               focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
               dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
               @error($field) border-red-400 dark:border-red-500 @enderror">
    @error($field)
        <p class="text-xs text-red-500 mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error</span>
            {{ $message }}
        </p>
    @enderror
</div>
