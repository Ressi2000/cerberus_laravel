<x-app-layout title="Monitor de red" header="Monitor de red">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Monitor de red', 'url' => route('admin.monitor-red.index')],
        ['label' => 'Enlaces de internet', 'url' => '#'],
    ]" />

    <x-form.success />

    @include('monitor-red._nav')

    @livewire('monitor-red.enlaces-table')

</x-app-layout>
