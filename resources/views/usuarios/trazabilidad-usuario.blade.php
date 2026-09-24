<x-app-layout title="Trazabilidad del Usuario" header="Gestión de Usuarios">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',          'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios','url' => '#'],
        ['label' => 'Usuarios',           'url' => route('admin.usuarios.index')],
        ['label' => $usuario->name,       'url' => '#'],
        ['label' => 'Trazabilidad',       'url' => '#'],
    ]" />

    @livewire('usuarios.trazabilidad-usuario', ['usuario' => $usuario])

</x-app-layout>
