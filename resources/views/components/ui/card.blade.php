@props(['title', 'description' => null])

<div {{ $attributes->merge([
    'class' => '
        bg-white dark:bg-cerberus-mid
        border border-gray-200 dark:border-cerberus-steel/60
        shadow-sm dark:shadow-none
        rounded-xl overflow-hidden
    ',
]) }}>
    {{-- Header de la card --}}
    <div class="px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel/40
                bg-gray-50/40 dark:bg-cerberus-dark/20">
        <h2 class="text-base font-semibold text-[#1E293B] dark:text-white">
            {{ $title }}
        </h2>
        @if ($description)
            <p class="text-sm text-gray-500 dark:text-cerberus-light mt-0.5">
                {{ $description }}
            </p>
        @endif
    </div>

    {{-- Contenido --}}
    <div class="p-6">
        {{ $slot }}
    </div>
</div>