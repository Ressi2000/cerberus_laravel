<x-app-layout title="Detalle del caso" header="Mantenimientos">

    <x-ui.breadcrumb :items="$lote ? [
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Cronograma',   'url' => route('admin.cronograma.index')],
        ['label' => $lote->categoria->nombre . ' — ' . $lote->empresa->nombre, 'url' => route('admin.cronograma.lotes.show', $lote)],
        ['label' => $mantenimiento->equipo->codigo_interno ?? '#' . $mantenimiento->id, 'url' => '#'],
    ] : [
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Mantenimientos', 'url' => route('admin.mantenimientos.index')],
        ['label' => $mantenimiento->equipo->codigo_interno ?? '#' . $mantenimiento->id, 'url' => '#'],
    ]" />

    @livewire('mantenimientos.mantenimiento-detalle', ['mantenimientoId' => $mantenimiento->id])

</x-app-layout>
