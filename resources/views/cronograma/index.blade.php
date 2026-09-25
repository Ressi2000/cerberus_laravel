<x-app-layout title="Cronograma de Mantenimiento" header="Cronograma">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',   'url' => route('dashboard')],
        ['label' => 'Cronograma',  'url' => '#'],
    ]" />

    <x-form.success />

    <div x-data="{ vista: 'lista' }" class="space-y-4">

        <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-cerberus-dark/40 border border-gray-200 dark:border-cerberus-steel rounded-lg p-1">
            <button @click="vista = 'lista'"
                :class="vista === 'lista'
                    ? 'bg-white dark:bg-cerberus-mid text-cerberus-primary dark:text-white shadow-sm'
                    : 'text-gray-500 dark:text-cerberus-light hover:text-gray-700 dark:hover:text-white'"
                class="px-4 py-1.5 text-sm font-medium rounded-md transition flex items-center gap-1.5">
                <span class="material-icons text-base">table_rows</span> Lista
            </button>
            <button @click="vista = 'calendario'; $nextTick(() => $dispatch('cronograma-calendario-visible'))"
                :class="vista === 'calendario'
                    ? 'bg-white dark:bg-cerberus-mid text-cerberus-primary dark:text-white shadow-sm'
                    : 'text-gray-500 dark:text-cerberus-light hover:text-gray-700 dark:hover:text-white'"
                class="px-4 py-1.5 text-sm font-medium rounded-md transition flex items-center gap-1.5">
                <span class="material-icons text-base">calendar_month</span> Calendario
            </button>
        </div>

        <div x-show="vista === 'lista'">
            @livewire('cronograma.planes-mantenimiento-table')
        </div>

        <div x-show="vista === 'calendario'" x-cloak>
            @livewire('cronograma.cronograma-calendario')
        </div>

    </div>

</x-app-layout>
