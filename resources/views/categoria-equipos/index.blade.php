<x-app-layout title="Categoría de los Equipos" header="Categoría de Equipos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Inventario', 'url' => '#'],
        ['label' => 'Equipos', 'url' => route('admin.categoria-equipos.index')],
    ]" />

    <x-form.success />

    @livewire('categoria-equipos.equipo-view-modal')
    @livewire('categoria-equipos.equipo-delete-modal')

    @livewire('categoria-equipos.categoria-equipos-table')

</x-app-layout>
