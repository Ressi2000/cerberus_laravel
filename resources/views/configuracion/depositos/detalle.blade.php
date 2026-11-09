<x-app-layout title="Contenido del depósito" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',      'url' => route('dashboard')],
        ['label' => 'Configuración',  'url' => '#'],
        ['label' => 'Depósitos',      'url' => route('admin.configuracion.depositos')],
        ['label' => $deposito->nombre, 'url' => '#'],
    ]" />

    @livewire('configuracion.depositos.deposito-contenido', ['deposito' => $deposito])

</x-app-layout>
