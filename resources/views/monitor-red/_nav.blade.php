{{-- Pestañas del Monitor de red + interruptor de alertas en el navegador --}}
@php
    $tabs = [
        ['route' => 'admin.monitor-red.index',     'label' => 'Dashboard',          'icon' => 'insights'],
        ['route' => 'admin.monitor-red.hosts',     'label' => 'Hosts',              'icon' => 'lan'],
        ['route' => 'admin.monitor-red.enlaces',   'label' => 'Enlaces',            'icon' => 'router'],
        ['route' => 'admin.monitor-red.caidas',    'label' => 'Historial de caídas', 'icon' => 'history'],
        ['route' => 'admin.monitor-red.velocidad', 'label' => 'Test de velocidad',  'icon' => 'speed'],
    ];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5">
    <nav class="flex flex-wrap gap-1 p-1 rounded-xl bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel/60">
        @foreach ($tabs as $tab)
            @php $on = request()->routeIs($tab['route']); @endphp
            <a href="{{ route($tab['route']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors
                      {{ $on
                          ? 'bg-[#1E40AF] text-white dark:bg-cerberus-primary'
                          : 'text-gray-600 dark:text-cerberus-light hover:bg-gray-100 dark:hover:bg-cerberus-steel/25' }}">
                <span class="material-icons text-base">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- Sonido + notificación de escritorio cuando un host cae / vuelve (preferencia de este navegador) --}}
    <div x-data="monitorRedAlertas()" @monitor-red-cambios.window="avisar($event.detail.cambios)" class="sm:ml-auto">
        <button type="button" @click="alternar()"
                :title="activas ? 'Desactivar el sonido y las notificaciones de escritorio' : 'Activar sonido y notificación de escritorio cuando un host cae o se recupera (solo con esta página abierta)'"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm border transition-colors"
                :class="activas
                    ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-300 dark:border-emerald-500/40 text-emerald-700 dark:text-emerald-400'
                    : 'bg-white dark:bg-cerberus-mid border-gray-200 dark:border-cerberus-steel text-gray-600 dark:text-cerberus-light'">
            <span class="material-icons text-base" x-text="activas ? 'notifications_active' : 'notifications_off'"></span>
            <span x-text="activas ? 'Alertas en pantalla activas' : 'Alertas en pantalla'"></span>
        </button>
    </div>
</div>
