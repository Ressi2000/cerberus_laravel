@props(['items' => []])

<nav aria-label="Breadcrumb" class="flex mb-5">
    <ol class="inline-flex items-center gap-1 flex-wrap">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1">

                @if (!$loop->last)
                    {{-- Item intermedio: enlace --}}
                    <a href="{{ $item['url'] }}"
                       class="inline-flex items-center gap-1.5 text-xs font-medium
                              text-gray-500 dark:text-cerberus-steel
                              hover:text-[#1E40AF] dark:hover:text-cerberus-accent
                              transition-colors duration-150 group">

                        {{-- Ícono de home solo en el primero --}}
                        @if ($loop->first)
                            <span class="material-icons text-sm opacity-70 group-hover:opacity-100 transition-opacity">
                                home
                            </span>
                        @endif

                        <span>{{ $item['label'] }}</span>
                    </a>

                    {{-- Separador --}}
                    <svg class="w-3.5 h-3.5 text-gray-300 dark:text-cerberus-steel/40 flex-shrink-0"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>

                @else
                    {{-- Último item: texto activo (no es enlace) --}}
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold
                                 text-[#1E40AF] dark:text-cerberus-accent
                                 bg-[#1E40AF]/8 dark:bg-cerberus-primary/15
                                 px-2.5 py-1 rounded-md
                                 border border-[#1E40AF]/15 dark:border-cerberus-primary/20"
                          aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif

            </li>
        @endforeach
    </ol>
</nav>