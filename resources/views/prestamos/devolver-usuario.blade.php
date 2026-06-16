<x-app-layout title="Registrar Devolución" header="Registrar Devolución">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',  'url' => route('dashboard')],
        ['label' => 'Préstamos',  'url' => route('admin.prestamos.index')],
        ['label' => $usuario->name, 'url' => route('admin.prestamos.historial', $usuario)],
        ['label' => 'Devolución', 'url' => '#'],
    ]" />

    @livewire('prestamos.devolver-usuario', ['usuario' => $usuario])

</x-app-layout>
