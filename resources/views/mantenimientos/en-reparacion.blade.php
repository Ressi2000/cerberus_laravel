<x-app-layout title="En Reparación" header="Mantenimientos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Mantenimientos', 'url' => route('admin.mantenimientos.index')],
        ['label' => 'En Reparación',  'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('mantenimientos.mantenimientos-table', ['soloReparacion' => true])

</x-app-layout>
