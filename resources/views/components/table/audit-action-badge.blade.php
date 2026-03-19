@props(['accion'])

@php
    $map = [
        'create'               => 'bg-green-400/10 text-green-400 ring-green-500/20',
        'CREAR'                => 'bg-green-400/10 text-green-400 ring-green-500/20',
        'update'               => 'bg-blue-400/10 text-blue-400 ring-blue-500/20',
        'EDITAR'               => 'bg-blue-400/10 text-blue-400 ring-blue-500/20',
        'delete'               => 'bg-red-400/10 text-red-400 ring-red-400/20',
        'ELIMINAR'             => 'bg-red-400/10 text-red-400 ring-red-400/20',
        'login'                => 'bg-purple-400/10 text-purple-400 ring-purple-500/20',
        'LOGIN'                => 'bg-purple-400/10 text-purple-400 ring-purple-500/20',
        'logout'               => 'bg-gray-400/10 text-gray-400 ring-gray-500/20',
        'LOGOUT'               => 'bg-gray-400/10 text-gray-400 ring-gray-500/20',
        'PASSWORD_RESET'       => 'bg-yellow-400/10 text-yellow-400 ring-yellow-500/20',
        'CAMBIO_EMPRESA_ACTIVA'=> 'bg-teal-400/10 text-teal-400 ring-teal-500/20',
    ];

    $class = $map[$accion] ?? 'bg-yellow-400/10 text-yellow-400 ring-yellow-500/20';

    $labels = [
        'create'               => 'Crear',
        'CREAR'                => 'Crear',
        'update'               => 'Editar',
        'EDITAR'               => 'Editar',
        'delete'               => 'Eliminar',
        'ELIMINAR'             => 'Eliminar',
        'login'                => 'Login',
        'LOGIN'                => 'Login',
        'logout'               => 'Logout',
        'LOGOUT'               => 'Logout',
        'PASSWORD_RESET'       => 'Reset pwd',
        'CAMBIO_EMPRESA_ACTIVA'=> 'Cambio empresa',
    ];

    $label = $labels[$accion] ?? ucfirst(strtolower($accion));
@endphp

<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $class }}">
    {{ $label }}
</span>
