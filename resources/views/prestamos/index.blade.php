<x-app-layout>
    <x-slot name="header">
        <x-ui.breadcrumb :items="[['label' => 'Préstamos']]" />
    </x-slot>

    @livewire('prestamos.prestamos-table')
</x-app-layout>
