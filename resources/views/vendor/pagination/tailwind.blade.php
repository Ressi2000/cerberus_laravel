@if ($paginator->hasPages())
    <div class="flex items-center justify-between">

        {{-- LEFT: Showing X–Y of Z --}}
        <div class="text-sm text-cerberus-light">
            Showing
            <span class="text-white font-semibold">{{ $paginator->firstItem() }}</span>
            –
            <span class="text-white font-semibold">{{ $paginator->lastItem() }}</span>
            of
            <span class="text-white font-semibold">{{ $paginator->total() }}</span>
        </div>

        <div class="flex items-center gap-4">

            {{-- SELECTOR PER PAGE --}}
            <form>
                <select name="per_page" onchange="this.form.submit()"
                    class="bg-cerberus-dark border border-cerberus-steel text-white rounded-md px-2 py-1 text-sm cursor-pointer">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" 
                            class="bg-cerberus-dark text-white"
                            {{ request('per_page', $paginator->perPage()) == $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </form>

            {{-- PAGINATION --}}
            <nav class="flex items-center gap-1 border border-cerberus-steel rounded-md bg-cerberus-dark px-1">

                {{-- Prev --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-1 text-gray-600 cursor-not-allowed"><svg viewBox="0 0 20 20"
                            fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
                            <path
                                d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                                clip-rule="evenodd" fill-rule="evenodd" />
                        </svg></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}"
                        class="px-3 py-1 text-white hover:bg-cerberus-steel/30 rounded">
                        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
                            <path
                                d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                                clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                    </a>
                @endif

                {{-- PAGE NUMBERS (compact) --}}
                @php
                    $current = $paginator->currentPage();
                    $last = $paginator->lastPage();
                    $window = 1;
                    $pages = collect();

                    for ($i = max(1, $current - $window); $i <= min($last, $current + $window); $i++) {
                        $pages->push($i);
                    }
                @endphp

                {{-- 1 --}}
                @if (!$pages->contains(1))
                    <a href="{{ $paginator->url(1) }}" class="px-3 py-1 text-white hover:bg-cerberus-steel/30 rounded">
                        1
                    </a>
                    <span class="px-2 text-gray-500">…</span>
                @endif

                {{-- dynamic pages --}}
                @foreach ($pages as $page)
                    @if ($page == $current)
                        <span class="px-3 py-1 bg-cerberus-primary text-white font-bold rounded">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($page) }}"
                            class="px-3 py-1 text-white hover:bg-cerberus-steel/30 rounded">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach

                {{-- last --}}
                @if (!$pages->contains($last))
                    <span class="px-2 text-gray-500">…</span>
                    <a href="{{ $paginator->url($last) }}"
                        class="px-3 py-1 text-white hover:bg-cerberus-steel/30 rounded">
                        {{ $last }}
                    </a>
                @endif

                {{-- NEXT --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}"
                        class="px-3 py-1 text-white hover:bg-cerberus-steel/30 rounded">
                        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
                            <path
                                d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span class="px-3 py-1 text-gray-600 cursor-not-allowed"><svg viewBox="0 0 20 20"
                            fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
                            <path
                                d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd" fill-rule="evenodd" />
                        </svg></span>
                @endif

            </nav>
        </div>

    </div>
@endif
