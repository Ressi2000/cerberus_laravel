<x-app-layout title="Bitácora de Cerberus Bot" header="Cerberus Bot">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Cerberus Bot', 'url' => '#'],
        ['label' => 'Bitácora', 'url' => '#'],
    ]" />

    @livewire('asistente.consultas-asistente')

</x-app-layout>
