<x-app-layout title="Atributos EAV" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',     'url' => route('dashboard')],
        ['label' => 'Configuración', 'url' => '#'],
        ['label' => 'Atributos',     'url' => '#'],
    ]" />

    @livewire('configuracion.atributos.atributos-table')

</x-app-layout>