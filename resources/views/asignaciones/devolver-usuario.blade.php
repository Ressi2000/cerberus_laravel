<x-app-layout title="Registrar Devolución" header="Registrar Devolución">
 
    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',    'url' => route('dashboard')],
        ['label' => 'Asignaciones', 'url' => route('admin.asignaciones.index')],
        ['label' => $usuario->name, 'url' => route('admin.asignaciones.historial', $usuario)],
        ['label' => 'Devolución',   'url' => '#'],
    ]" />
 
    @livewire('asignaciones.devolver-usuario', ['usuario' => $usuario])
 
</x-app-layout>