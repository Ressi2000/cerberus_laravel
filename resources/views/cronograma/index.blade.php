<x-app-layout title="Cronograma de Mantenimiento" header="Cronograma">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',   'url' => route('dashboard')],
        ['label' => 'Cronograma',  'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('cronograma.planes-mantenimiento-table')

</x-app-layout>
