<x-app-layout>
    <x-slot name="header">
        <x-ui.breadcrumb :items="[
            ['label' => 'Préstamos', 'url' => route('admin.prestamos.index')],
            ['label' => 'Registrar devolución'],
        ]" />
    </x-slot>

    @livewire('prestamos.devolver-prestamo', ['prestamo' => $prestamo])
</x-app-layout>
