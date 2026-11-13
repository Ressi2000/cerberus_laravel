<x-app-layout title="Solicitudes pendientes" header="Almacén">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',              'url' => route('dashboard')],
        ['label' => 'Almacén',                'url' => route('admin.almacen.index')],
        ['label' => 'Solicitudes pendientes', 'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('almacen.solicitudes-pendientes')

</x-app-layout>
