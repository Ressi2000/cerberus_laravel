<x-app-layout title="Auditoría" header="Auditoría">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',   'url' => route('dashboard')],
        ['label' => 'Auditoría',   'url' => '#'],
    ]" />

    <x-ui.en-mejora
        titulo="Auditoría está en mejora"
        mensaje="Estamos trabajando en mejoras para el módulo de Auditoría. Vuelve a intentarlo más adelante."
    />

</x-app-layout>
