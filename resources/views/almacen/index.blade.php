<x-app-layout title="Almacén" header="Almacén">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Almacén',   'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('almacen.almacen-table')

</x-app-layout>
