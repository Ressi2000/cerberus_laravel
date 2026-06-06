<x-app-layout title="Nuevo Préstamo" header="Nuevo Préstamo">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Préstamos', 'url' => route('admin.prestamos.index')],
        ['label' => 'Nuevo préstamo', 'url' => '#'],
    ]" />

    @livewire('prestamos.crear-prestamo')

</x-app-layout>
