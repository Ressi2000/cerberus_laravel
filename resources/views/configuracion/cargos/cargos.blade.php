<x-app-layout title="Cargos" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Configuración','url' => '#'],
        ['label' => 'Cargos','url' => '#'],
    ]" />

    <x-form.success />

    @livewire('configuracion.cargos.cargo-view-modal')
    @livewire('configuracion.cargos.cargo-modal')
    @livewire('configuracion.cargos.cargo-delete-modal')

    @livewire('configuracion.cargos.cargos-table')

</x-app-layout>