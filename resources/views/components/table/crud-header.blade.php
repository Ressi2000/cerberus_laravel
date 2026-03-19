@props([
    'title',
    'subtitle' => null,
    'buttonLabel' => null,
    'buttonUrl' => null,
])

<div class="flex items-center justify-between mb-6">

    {{-- TITULO + SUBTITULO --}}
    <div>
        <h1 class="text-2xl font-bold text-white">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-cerberus-accent text-sm mt-1">{{ $subtitle }}</p>
        @endif
    </div>

    {{-- BOTÓN --}}
    @if ($buttonLabel && $buttonUrl)
        <a href="{{ $buttonUrl }}"
            class="inline-flex items-center px-4 py-2 bg-cerberus-primary hover:bg-cerberus-hover
                   text-white rounded-lg shadow transition-colors duration-150">
            <span class="material-icons mr-1 text-base">add</span>
            {{ $buttonLabel }}
        </a>
    @endif
</div>

{{-- SLOT PARA FILTROS PERSONALIZADOS --}}
@if (isset($filters))
    <div class="mt-4">
        {{ $filters }}
    </div>
@endif
