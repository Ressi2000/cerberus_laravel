{{-- Badge de estado de un host. Uso: @include('livewire.monitor-red._estado', ['h' => $host]) --}}
@php
    $etq = $h->etiquetaEstado();
@endphp
<span @class([
    'inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full border font-semibold whitespace-nowrap',
    'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30' => $etq === 'Activo',
    'bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/30' => $etq === 'Caído',
    'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30' => $etq === 'Pausado',
    'bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light border-gray-200 dark:border-cerberus-steel/30' => $etq === 'Pendiente',
])>
    <span class="material-icons text-xs">{{ ['Activo' => 'check_circle', 'Caído' => 'error', 'Pausado' => 'pause_circle', 'Pendiente' => 'schedule'][$etq] }}</span>
    {{ $etq }}
    @if ($etq === 'Pendiente' && $h->fallos_consecutivos > 0)
        <span class="font-normal">({{ $h->fallos_consecutivos }} fallo)</span>
    @endif
</span>
