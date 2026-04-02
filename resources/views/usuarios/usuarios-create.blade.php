<x-app-layout title="Crear Usuario" header="Gestión de Usuarios">
    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',          'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios','url' => '#'],
        ['label' => 'Usuarios',           'url' => route('admin.usuarios.index')],
        ['label' => 'Crear'],
    ]" />
    <x-form.success />
    @livewire('usuarios.crear-usuario')
</x-app-layout>
