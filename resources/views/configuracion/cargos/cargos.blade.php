<x-app-layout title="Cargos" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Configuración','url' => '#'],
        ['label' => 'Cargos','url' => '#'],
    ]" />

    @livewire('configuracion.cargos.cargos-table')

</x-app-layout>