@props(['items' => []])

<nav class="flex mb-6 text-sm text-cerberus-accent">
    <ol class="inline-flex items-center space-x-1">
        @foreach ($items as $index => $item)
            <li class="flex items-center">
                @if (!$loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-cerberus-light">
                        {{ $item['label'] }}
                    </a>
                    <span class="mx-2">/</span>
                @else
                    <span class="text-white font-medium">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
