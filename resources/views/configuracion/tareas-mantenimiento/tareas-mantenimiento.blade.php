<x-app-layout title="Tareas de Mantenimiento" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Configuración',  'url' => '#'],
        ['label' => 'Tareas de Mantenimiento', 'url' => '#'],
    ]" />

    @livewire('configuracion.tareas-mantenimiento.tareas-mantenimiento-table')

</x-app-layout>
