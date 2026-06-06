<x-app-layout title="Préstamos" header="Préstamos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Préstamos', 'url' => '#'],
    ]" />

    @livewire('prestamos.prestamos-table')

</x-app-layout>
