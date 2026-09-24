<x-app-layout title="Detalle del caso" header="Mantenimientos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Mantenimientos', 'url' => route('admin.mantenimientos.index')],
        ['label' => $mantenimiento->equipo->codigo_interno ?? '#' . $mantenimiento->id, 'url' => '#'],
    ]" />

    @livewire('mantenimientos.mantenimiento-detalle', ['mantenimientoId' => $mantenimiento->id])

</x-app-layout>
