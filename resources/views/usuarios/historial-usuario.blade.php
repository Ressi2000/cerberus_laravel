<x-app-layout title="Historial del Usuario" header="Gestión de Usuarios">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',          'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios','url' => '#'],
        ['label' => 'Usuarios',           'url' => route('admin.usuarios.index')],
        ['label' => $usuario->name,       'url' => '#'],
        ['label' => 'Historial',          'url' => '#'],
    ]" />

    @livewire('usuarios.historial-usuario', ['usuario' => $usuario])

</x-app-layout>
