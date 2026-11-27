<x-app-layout title="Cerberus Bot" header="Cerberus Bot">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Cerberus Bot', 'url' => '#'],
    ]" />

    @livewire('asistente.chat-asistente', ['pantallaCompleta' => true])

</x-app-layout>
