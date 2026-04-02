@props([
    'items' => []
])

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach ($items as $item)
        <div class="bg-white dark:bg-cerberus-mid
                    border border-gray-200 dark:border-cerberus-steel/60
                    shadow-sm dark:shadow-none
                    rounded-xl p-4 flex items-center gap-4
                    hover:shadow-md dark:hover:border-cerberus-steel
                    transition-all duration-200">

            {{-- Ícono --}}
            <div class="flex items-center justify-center w-10 h-10 rounded-xl flex-shrink-0
                        bg-[#1E40AF]/8 dark:bg-cerberus-primary/15">
                <span class="material-icons text-xl text-[#1E40AF] dark:text-cerberus-accent">
                    {{ $item['icon'] }}
                </span>
            </div>

            {{-- Texto --}}
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent truncate">
                    {{ $item['title'] }}
                </p>
                <p class="text-2xl font-bold text-[#1E293B] dark:text-white leading-tight">
                    {{ $item['value'] }}
                </p>
            </div>
        </div>
    @endforeach
</div>