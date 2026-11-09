<x-app-layout title="Mi Actividad" header="Actividad del Usuario">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Perfil', 'url' => route('profile.edit')],
        ['label' => 'Actividad'],
    ]" />

    <x-ui.en-mejora
        titulo="Mi Actividad está en mejora"
        mensaje="Estamos trabajando en mejoras para esta sección. Vuelve a intentarlo más adelante."
    />

</x-app-layout>
