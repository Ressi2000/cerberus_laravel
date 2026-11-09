<x-app-layout title="Tipos de Licencia Microsoft" header="Configuración">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',                     'url' => route('dashboard')],
        ['label' => 'Configuración',                 'url' => '#'],
        ['label' => 'Tipos de Licencia Microsoft',    'url' => '#'],
    ]" />

    @livewire('configuracion.licencias-microsoft.licencias-microsoft-table')

</x-app-layout>
