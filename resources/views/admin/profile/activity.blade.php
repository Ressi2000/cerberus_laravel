<x-app-layout title="Mi Actividad" header="Actividad del Usuario">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Perfil', 'url' => route('profile.edit')],
        ['label' => 'Actividad'],
    ]" />

    <x-form.success />
    
    {{-- LIVEWIRE TABLE --}}
    @livewire('admin.auditoria-table', ['isProfileView' => true])
</x-app-layout>
