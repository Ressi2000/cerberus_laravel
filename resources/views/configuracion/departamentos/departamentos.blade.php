<x-app-layout title="Departamentos" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Configuración','url' => '#'],
        ['label' => 'Departamentos','url' => '#'],
    ]" />

    @livewire('configuracion.departamentos.departamentos-table')

</x-app-layout>