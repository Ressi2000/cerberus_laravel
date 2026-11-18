<x-app-layout title="Renovación de equipos" header="Inventario">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Inventario', 'url' => '#'],
        ['label' => 'Renovación de equipos', 'url' => '#'],
    ]" />

    @livewire('equipos.renovacion-equipos')

</x-app-layout>
