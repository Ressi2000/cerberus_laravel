@php
    $scrollTo = $scrollTo ?? 'body';
@endphp

@if ($paginator->hasPages())
    <div class="flex flex-col gap-3">

        {{-- ================= MOBILE ================= --}}
        <div class="flex md:hidden items-center justify-between">

            {{-- PREVIOUS --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 text-gray-500 cursor-not-allowed select-none">
                    <span class="material-icons text-base">chevron_left</span>
                </span>
            @else
                <button
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    class="px-3 py-2 text-white hover:bg-cerberus-steel rounded-lg transition">
                    <span class="material-icons text-base">chevron_left</span>
                </button>
            @endif

            {{-- PAGE INFO --}}
            <span class="text-sm text-cerberus-light">
                Página
                <span class="font-semibold text-white">
                    {{ $paginator->currentPage() }}
                </span>
                de
                <span class="font-semibold text-white">
                    {{ $paginator->lastPage() }}
                </span>
            </span>

            {{-- NEXT --}}
            @if ($paginator->hasMorePages())
                <button
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    class="px-3 py-2 text-white hover:bg-cerberus-steel rounded-lg transition">
                    <span class="material-icons text-base">chevron_right</span>
                </button>
            @else
                <span class="px-3 py-2 text-gray-500 cursor-not-allowed select-none">
                    <span class="material-icons text-base">chevron_right</span>
                </span>
            @endif
        </div>

        {{-- ================= DESKTOP ================= --}}
        <div class="hidden md:flex md:items-center md:justify-between gap-4">

            {{-- INFO --}}
            <div class="text-sm text-cerberus-light">
                Mostrando
                <span class="font-semibold text-white">{{ $paginator->firstItem() }}</span>
                –
                <span class="font-semibold text-white">{{ $paginator->lastItem() }}</span>
                de
                <span class="font-semibold text-white">{{ $paginator->total() }}</span>
                registros
            </div>

            {{-- PAGINATION --}}
            <nav class="inline-flex items-center rounded-lg bg-cerberus-dark border border-cerberus-steel shadow-sm overflow-hidden">

                {{-- PREVIOUS --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-2 text-gray-500 cursor-not-allowed select-none">
                        <span class="material-icons text-base">chevron_left</span>
                    </span>
                @else
                    <button
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        class="px-3 py-2 text-white hover:bg-cerberus-steel transition">
                        <span class="material-icons text-base">chevron_left</span>
                    </button>
                @endif

                {{-- PAGES --}}
                @foreach ($elements as $element)

                    {{-- DOTS --}}
                    @if (is_string($element))
                        <span class="px-3 py-2 text-sm text-gray-400">…</span>
                    @endif

                    {{-- PAGE LINKS --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="page-{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span class="px-4 py-2 text-sm font-semibold bg-cerberus-primary text-white">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        class="px-4 py-2 text-sm text-cerberus-light hover:bg-cerberus-steel transition">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif

                @endforeach

                {{-- NEXT --}}
                @if ($paginator->hasMorePages())
                    <button
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        class="px-3 py-2 text-white hover:bg-cerberus-steel transition">
                        <span class="material-icons text-base">chevron_right</span>
                    </button>
                @else
                    <span class="px-3 py-2 text-gray-500 cursor-not-allowed select-none">
                        <span class="material-icons text-base">chevron_right</span>
                    </span>
                @endif

            </nav>
        </div>
    </div>
@endif
