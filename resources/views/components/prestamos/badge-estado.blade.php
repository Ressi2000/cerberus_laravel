@props(['estado'])

@php
$config = match($estado) {
    'Activo'  => ['bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-700/40', 'swap_horiz', 'Activo'],
    'Vencido' => ['bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-700/40', 'schedule', 'Vencido'],
    'Cerrado' => ['bg-gray-100 dark:bg-cerberus-dark text-gray-600 dark:text-cerberus-steel border-gray-200 dark:border-cerberus-steel/40', 'lock', 'Cerrado'],
    default   => ['bg-gray-100 dark:bg-cerberus-dark text-gray-500 border-gray-200', 'help', $estado],
};
[$classes, $icon, $label] = $config;
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $classes }}">
    <span class="material-icons text-xs">{{ $icon }}</span>
    {{ $label }}
</span>
