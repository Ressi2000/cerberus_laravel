<x-app-layout title="Lote de Mantenimiento" header="Cronograma">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',   'url' => route('dashboard')],
        ['label' => 'Cronograma',  'url' => route('admin.cronograma.index')],
        ['label' => $plan->categoria->nombre . ' — ' . $plan->empresa->nombre, 'url' => '#'],
    ]" />

    @livewire('mantenimientos.reportar-problema-modal')

    @livewire('cronograma.lote-detalle', ['planId' => $plan->id])

</x-app-layout>
