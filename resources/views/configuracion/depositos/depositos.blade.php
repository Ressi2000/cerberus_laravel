<x-app-layout title="Depósitos" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Configuración',  'url' => '#'],
        ['label' => 'Depósitos',      'url' => '#'],
    ]" />

    @livewire('configuracion.depositos.depositos-table')

</x-app-layout>
