@props(['estado'])

@php
    $config = match($estado) {
        'Activa'  => ['bg' => 'bg-emerald-900/40 text-emerald-400 border-emerald-700/50', 'icon' => 'check_circle'],
        'Parcial' => ['bg' => 'bg-amber-900/40 text-amber-400 border-amber-700/50',       'icon' => 'remove_circle_outline'],
        'Cerrada' => ['bg' => 'bg-cerberus-steel/30 text-cerberus-accent border-cerberus-steel/50', 'icon' => 'lock'],
        default   => ['bg' => 'bg-cerberus-steel/20 text-cerberus-light border-cerberus-steel/40',  'icon' => 'help_outline'],
    };
@endphp

<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border {{ $config['bg'] }}">
    <span class="material-icons text-xs">{{ $config['icon'] }}</span>
    {{ $estado }}
</span>