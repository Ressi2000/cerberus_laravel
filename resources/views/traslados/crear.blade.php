<x-app-layout title="Nuevo Traslado" header="Nuevo Traslado">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',  'url' => route('dashboard')],
        ['label' => 'Traslados',  'url' => route('admin.traslados.index')],
        ['label' => 'Nuevo traslado', 'url' => '#'],
    ]" />

    @livewire('traslados.crear-traslado')

</x-app-layout>
