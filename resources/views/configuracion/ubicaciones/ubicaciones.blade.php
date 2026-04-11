<x-app-layout title="Ubicaciones" header="Configuración — Ubicaciones">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Configuración','url' => '#'],
        ['label' => 'Ubicaciones',  'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('configuracion.ubicaciones.ubicacion-view-modal')
    @livewire('configuracion.ubicaciones.ubicacion-modal')
    @livewire('configuracion.ubicaciones.ubicacion-delete-modal')

    @livewire('configuracion.ubicaciones.ubicaciones-table')

</x-app-layout>