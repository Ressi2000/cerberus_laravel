<x-app-layout title="Auditoría" header="Auditoría del sistema">

    {{-- BREADCRUMB --}}
    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Auditoría', 'url' => route('admin.auditoria.index')],
    ]" />

    <x-form.success />
    
    {{-- LIVEWIRE TABLE --}}
    @livewire('admin.auditoria-table')

</x-app-layout>
