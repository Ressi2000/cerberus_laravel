<x-app-layout title="Historial de Asignaciones" header="Historial de Asignaciones">
 
    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Asignaciones', 'url' => route('admin.asignaciones.index')],
        ['label' => $usuario->name, 'url' => '#'],
    ]" />
 
    @livewire('asignaciones.historial-usuario', ['usuario' => $usuario])
 
</x-app-layout>