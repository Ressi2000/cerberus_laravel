<x-app-layout title="Rotación de Asignaciones" header="Asignaciones">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Asignaciones','url' => route('admin.asignaciones.index')],
        ['label' => 'Rotación recomendada', 'url' => '#'],
    ]" />

    @livewire('asignaciones.rotacion-recomendada')

</x-app-layout>
