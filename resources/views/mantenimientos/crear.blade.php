<x-app-layout title="Nuevo Mantenimiento" header="Mantenimientos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Mantenimientos', 'url' => route('admin.mantenimientos.index')],
        ['label' => 'Nuevo',          'url' => '#'],
    ]" />

    @livewire('mantenimientos.crear-mantenimiento')

</x-app-layout>
