<x-app-layout title="Empresas" header="Configuración — Empresas">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Configuración','url' => '#'],
        ['label' => 'Empresas',     'url' => '#'],
    ]" />


    @livewire('configuracion.empresas.empresa-view-modal')
    @livewire('configuracion.empresas.empresa-modal')
    @livewire('configuracion.empresas.empresa-delete-modal')

    @livewire('configuracion.empresas.empresas-table')

</x-app-layout>