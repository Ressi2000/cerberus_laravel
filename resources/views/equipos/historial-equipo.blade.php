<x-app-layout title="Historial del Equipo" header="Gestión de Equipos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Inventario', 'url' => '#'],
        ['label' => 'Equipos', 'url' => route('admin.equipos.index')],
        ['label' => $equipo->codigo_interno, 'url' => '#'],
        ['label' => 'Historial', 'url' => '#'],
    ]" />

    @livewire('equipos.historial-equipo', ['equipo' => $equipo])

</x-app-layout>