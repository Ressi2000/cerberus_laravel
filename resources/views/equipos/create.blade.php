<x-app-layout title="Equipos" header="Gestión de Equipos">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Inventario', 'url' => '#'],
        ['label' => 'Equipos', 'url' => route('admin.equipos.index')],
        ['label' => 'Crear Equipo', 'url' => '#'],
    ]" />

    <x-form.success />

    @livewire('equipos.crear-equipo')

</x-app-layout>