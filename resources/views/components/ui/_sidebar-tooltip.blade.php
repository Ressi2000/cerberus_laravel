{{--
    Partial: _sidebar-tooltip.blade.php
    Uso: @include('components.ui._sidebar-tooltip', ['label' => 'Nombre'])

    Muestra un tooltip a la derecha del sidebar cuando está en modo mini.
    Controlado por CSS: visible cuando html[data-sidebar="mini"]
--}}
<span class="sidebar-tooltip
             absolute left-full top-1/2 -translate-y-1/2 ml-3
             px-2.5 py-1.5 text-xs font-medium
             bg-gray-900 dark:bg-cerberus-dark
             text-white rounded-lg shadow-lg
             whitespace-nowrap pointer-events-none
             opacity-0 invisible
             group-hover:opacity-100 group-hover:visible
             transition-all duration-150 z-50">
    {{ $label }}
    {{-- Flecha izquierda --}}
    <span class="absolute right-full top-1/2 -translate-y-1/2
                 border-4 border-transparent border-r-gray-900 dark:border-r-cerberus-dark">
    </span>
</span>

{{--
    CSS adicional para mostrar/ocultar el tooltip según modo sidebar.
    Usamos un <style> inline aquí para no contaminar el CSS global.
    Solo se aplica cuando data-sidebar="mini".
--}}
<style>
    /* En modo full: tooltips ocultos siempre */
    html[data-sidebar="full"] .sidebar-tooltip {
        display: none !important;
    }
</style>