@props([
    'items' => []
])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    @foreach ($items as $item)
        <div class="bg-cerberus-mid dark:bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-lg p-5 flex items-center gap-4">

            <div class="flex items-center justify-center bg-cerberus-steel/40 rounded-lg p-3">
                <span class="material-icons text-cerberus-light text-3xl">
                    {{ $item['icon'] }}
                </span>
            </div>

            <div>
                <p class="text-cerberus-accent text-sm">{{ $item['title'] }}</p>
                <h3 class="text-cerberus-textdark dark:text-white text-2xl font-bold">{{ $item['value'] }}</h3>
            </div>

        </div>
    @endforeach

</div>
