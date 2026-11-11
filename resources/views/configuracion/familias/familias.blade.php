<x-app-layout title="Familias de piezas" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',          'url' => route('dashboard')],
        ['label' => 'Configuración',      'url' => '#'],
        ['label' => 'Familias de piezas', 'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('configuracion.familias.familias-table')

</x-app-layout>
