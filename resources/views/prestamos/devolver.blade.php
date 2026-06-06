<x-app-layout title="Registrar Devolución" header="Registrar Devolución">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',            'url' => route('dashboard')],
        ['label' => 'Préstamos',            'url' => route('admin.prestamos.index')],
        ['label' => 'Registrar devolución', 'url' => '#'],
    ]" />

    @livewire('prestamos.devolver-prestamo', ['prestamo' => $prestamo])

</x-app-layout>
