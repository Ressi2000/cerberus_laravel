<div class="space-y-6">

    @livewire('configuracion.familias.familia-modal')

    <x-ui.stats-cards :items="[
        ['title' => 'Familias activas',            'value' => $this->stats['familias'],   'icon' => 'category'],
        ['title' => 'Características que las usan', 'value' => $this->stats['vinculadas'], 'icon' => 'link'],
        ['title' => 'Inactivas',                   'value' => $this->stats['inactivas'],  'icon' => 'visibility_off'],
    ]" />

    <x-table.crud-header
        title="Familias de piezas"
        subtitle="Ficha técnica única de cada tipo de pieza (RAM, Discos…), compartida por todas las categorías de equipos"
        buttonLabel="Nueva familia"
        buttonEvent="openFamiliaCrear">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input label="Buscar" wire:model.live.500ms="search" placeholder="Nombre de la familia..." />
                </div>
                <div class="flex items-center gap-3">
                    <button wire:click="$toggle('mostrar_inactivas')" role="switch"
                        aria-checked="{{ $mostrar_inactivas ? 'true' : 'false' }}"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200
                               {{ $mostrar_inactivas ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200
                                     {{ $mostrar_inactivas ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">Mostrar inactivas</span>
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    <x-table.crud-table :headers="['Familia', 'Control', 'Ficha técnica', 'La usan', 'Estado', 'Acciones']">
        @forelse ($this->familias as $familia)
            <tr wire:key="fam-{{ $familia->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30 hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150
                       {{ ! $familia->activo ? 'opacity-60' : '' }}">
                <td class="px-4 py-3">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $familia->nombre }}</p>
                    @if ($familia->descripcion)
                        <p class="text-xs text-gray-500 dark:text-cerberus-accent max-w-xs truncate">{{ $familia->descripcion }}</p>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                 {{ $familia->control === 'unidad'
                                     ? 'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-300'
                                     : 'bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-300' }}">
                        <span class="material-icons text-xs">{{ $familia->control === 'unidad' ? 'qr_code_2' : 'inventory' }}</span>
                        {{ $familia->control === 'unidad' ? 'Por unidad' : 'A granel' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1.5 max-w-md">
                        @foreach ($familia->campos ?? [] as $campo)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                         bg-gray-100 dark:bg-cerberus-steel/20 text-gray-700 dark:text-cerberus-light">
                                {{ $campo['nombre'] }}
                                @if ($campo['debe_coincidir'] ?? false)
                                    <span class="material-icons text-xs text-teal-600 dark:text-teal-400" title="Debe coincidir para que la pieza encaje">rule</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-cerberus-light">
                    @forelse ($familia->atributos as $atributo)
                        <span class="block">{{ $atributo->categoria->nombre ?? '—' }} <span class="text-gray-400 dark:text-cerberus-steel">· {{ $atributo->nombre }}</span></span>
                    @empty
                        <span class="text-gray-400 dark:text-cerberus-steel">Ninguna categoría todavía</span>
                    @endforelse
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex px-2 py-0.5 text-xs rounded-full
                                 {{ $familia->activo
                                     ? 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400'
                                     : 'bg-gray-100 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-accent' }}">
                        {{ $familia->activo ? 'Activa' : 'Inactiva' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                        <button wire:click="$dispatch('openFamiliaEditar', { id: {{ $familia->id }} })" title="Editar ficha"
                            class="p-1.5 rounded-lg text-gray-500 dark:text-cerberus-accent hover:text-[#1E40AF] dark:hover:text-white hover:bg-gray-100 dark:hover:bg-cerberus-steel/20 transition">
                            <span class="material-icons text-base">edit</span>
                        </button>
                        <button wire:click="cambiarActivo({{ $familia->id }})" title="{{ $familia->activo ? 'Desactivar' : 'Reactivar' }}"
                            class="p-1.5 rounded-lg text-gray-500 dark:text-cerberus-accent hover:text-[#1E40AF] dark:hover:text-white hover:bg-gray-100 dark:hover:bg-cerberus-steel/20 transition">
                            <span class="material-icons text-base">{{ $familia->activo ? 'visibility_off' : 'visibility' }}</span>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-4xl block mb-2 opacity-40">category</span>
                    No hay familias todavía. Crea la primera, por ejemplo «RAM».
                </td>
            </tr>
        @endforelse
    </x-table.crud-table>
</div>
