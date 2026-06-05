<x-app-layout title="Equipos" header="Gestión de Equipos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Inventario', 'url' => '#'],
        ['label' => 'Equipos', 'url' => route('admin.equipos.index')],
    ]" />


    @livewire('equipos.equipos-table')

</x-app-layout>
