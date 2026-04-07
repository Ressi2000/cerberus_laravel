<x-app-layout title="Nueva Asignación" header="Nueva Asignación">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Asignaciones', 'url' => route('admin.asignaciones.index')],
        ['label' => 'Nueva asignación', 'url' => '#'],
    ]" />

    @livewire('asignaciones.crear-asignacion')

</x-app-layout>