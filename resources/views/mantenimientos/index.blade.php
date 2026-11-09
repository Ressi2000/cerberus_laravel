<x-app-layout title="Mantenimientos" header="Mantenimientos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Mantenimientos', 'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('mantenimientos.mantenimientos-table')

</x-app-layout>
