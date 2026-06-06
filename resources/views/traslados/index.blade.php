<x-app-layout title="Traslados" header="Traslados">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Traslados', 'url' => '#'],
    ]" />

    @livewire('traslados.traslados-table')

</x-app-layout>
