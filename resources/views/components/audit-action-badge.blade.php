@props(['accion'])

@php
    $map = [
        'create' => 'bg-green-400/10 text-green-400 inset-ring-green-500/20',
        'update' => 'bg-blue-400/10 text-blue-400 inset-ring-blue-500/20',
        'delete' => 'bg-red-400/10 text-red-400 inset-ring-red-500/20',
        'login'  => 'bg-purple-400/10 text-purple-400 inset-ring-purple-500/20',
        'logout' => 'bg-gray-400/10 text-gray-400 inset-ring-gray-500/20',
    ];

    $class = $map[$accion] ?? 'bg-yellow-400/10 text-yellow-400';
@endphp

<span
    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium inset-ring {{ $class }}">
    {{ ucfirst($accion) }}
</span>
