<x-app-layout title="Departamentos" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Configuración','url' => '#'],
        ['label' => 'Departamentos','url' => '#'],
    ]" />


    @livewire('configuracion.departamentos.departamento-view-modal')
    @livewire('configuracion.departamentos.departamento-modal')
    @livewire('configuracion.departamentos.departamento-delete-modal')

    @livewire('configuracion.departamentos.departamentos-table')

</x-app-layout>