{{-- resources/views/admin/asignaciones/devolver.blade.php --}}
<x-app-layout title="Registrar Devolución" header="Registrar Devolución">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',             'url' => route('dashboard')],
        ['label' => 'Asignaciones',          'url' => route('admin.asignaciones.index')],
        ['label' => 'Registrar devolución',  'url' => '#'],
    ]" />

    @livewire('asignaciones.devolver-asignacion', ['asignacion' => $asignacion])

</x-app-layout>