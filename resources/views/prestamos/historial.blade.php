<x-app-layout title="Historial de Préstamos" header="Historial de Préstamos">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',  'url' => route('dashboard')],
        ['label' => 'Préstamos',  'url' => route('admin.prestamos.index')],
        ['label' => $usuario->name, 'url' => '#'],
    ]" />

    @livewire('prestamos.historial-usuario', ['usuario' => $usuario])

</x-app-layout>
