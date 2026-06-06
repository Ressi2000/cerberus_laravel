<x-app-layout>
    <x-slot name="header">
        <x-ui.breadcrumb :items="[
            ['label' => 'Préstamos', 'url' => route('admin.prestamos.index')],
            ['label' => 'Nuevo préstamo'],
        ]" />
    </x-slot>

    @livewire('prestamos.crear-prestamo')
</x-app-layout>
