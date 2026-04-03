@props([
    'title',
    'subtitle'    => null,
    'buttonLabel' => null,
    'buttonUrl'   => null,
    'buttonEvent' => null,
])

{{-- ── HEADER ───────────────────────────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">

    <div class="min-w-0">
        <h1 class="text-xl font-bold text-[#1E293B] dark:text-white truncate">
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="text-sm text-gray-500 dark:text-cerberus-accent mt-0.5">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @if ($buttonLabel && $buttonUrl)
        <a href="{{ $buttonUrl }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                  bg-[#1E40AF] hover:bg-[#1E3A8A]
                  text-white shadow-sm
                  transition-all duration-150 flex-shrink-0
                  focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30">
            <span class="material-icons text-base">add</span>
            {{ $buttonLabel }}
        </a>
    @endif

    {{-- Botón disparar evento (abre modal) --}}
    @if ($buttonLabel && $buttonEvent)
        <button wire:click="$dispatch('{{ $buttonEvent }}')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                       bg-[#1E40AF] hover:bg-[#1E3A8A] text-white shadow-sm
                       transition-all duration-150 flex-shrink-0
                       focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30">
            <span class="material-icons text-base">add</span>
            {{ $buttonLabel }}
        </button>
    @endif
</div>

{{-- ── FILTROS (slot) ──────────────────────────────────────────────────────── --}}
@if (isset($filters))
    <div class="mb-5">
        {{ $filters }}
    </div>
@endif