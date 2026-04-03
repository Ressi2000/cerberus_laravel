<x-app-layout title="Categorías" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Configuración',  'url' => '#'],
        ['label' => 'Categorías',     'url' => '#'],
    ]" />

    @livewire('configuracion.categorias.categorias-table')

</x-app-layout>