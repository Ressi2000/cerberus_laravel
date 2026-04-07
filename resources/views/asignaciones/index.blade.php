<x-app-layout title="Asignaciones" header="Asignaciones">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Asignaciones', 'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('asignaciones.asignaciones-table')

</x-app-layout>