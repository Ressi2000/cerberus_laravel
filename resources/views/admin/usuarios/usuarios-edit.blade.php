<x-app-layout title="Editar Usuario" header="Gestión de Usuarios">
    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',          'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios','url' => '#'],
        ['label' => 'Usuarios',           'url' => route('admin.usuarios.index')],
        ['label' => 'Editar'],
    ]" />
    <x-form.success />
    @livewire('admin.editar-usuario', ['usuarioId' => $usuario->id])
</x-app-layout>
