<x-app-layout title="Usuarios" header="Gestión de Usuarios">

    {{-- BREADCRUMB --}}
    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios', 'url' => '#'],
        ['label' => 'Usuarios', 'url' => route('admin.usuarios.index')],
    ]" />
    
    <x-form.success />
    
    {{-- LIVEWIRE TABLE --}}
    @livewire('usuarios.usuarios-table')

</x-app-layout>