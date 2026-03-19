@props(['title', 'description' => null])

<div
    {{ $attributes->merge([
        'class' => '
            bg-cerberus-mid
            dark:bg-cerberus-mid
            border border-cerberus-steel
            shadow-cerberus
            rounded-xl
        ',
    ]) }}>
    <div class="px-6 py-4 border-b border-cerberus-steel">
        <h2 class="text-lg font-semibold text-cerberus-textdark dark:text-white">
            {{ $title }}
        </h2>

        @if ($description)
            <p class="text-sm text-cerberus-textsoft dark:text-cerberus-light mt-1">
                {{ $description }}
            </p>
        @endif
    </div>

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
