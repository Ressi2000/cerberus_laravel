@props(['estado'])

@php
    // Gris por defecto si el equipo no tiene estado o el estado no tiene color asignado.
    $color = $estado?->color ?? '#64748B';
@endphp

<span {{ $attributes->merge([
        'class' => 'inline-flex items-center px-2 py-0.5 text-xs rounded-full font-medium border',
    ]) }}
    style="background-color: {{ $color }}1A; color: {{ $color }}; border-color: {{ $color }}4D;"
>
    {{ $estado?->nombre ?? '—' }}
</span>
