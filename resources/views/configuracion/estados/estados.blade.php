<x-app-layout title="Estados de Equipos" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',         'url' => route('dashboard')],
        ['label' => 'Configuración',     'url' => '#'],
        ['label' => 'Estados de Equipos','url' => '#'],
    ]" />

    @livewire('configuracion.estados.estados-table')

</x-app-layout>