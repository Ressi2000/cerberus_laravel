<div class="space-y-6">

    @livewire('configuracion.depositos.deposito-modal')

    <x-ui.stats-cards :items="[
        ['title' => 'Depósitos activos', 'value' => $this->total,          'icon' => 'warehouse'],
        ['title' => 'Inactivos',         'value' => $this->totalInactivos, 'icon' => 'block'],
    ]" />

    <x-table.crud-header
        title="Depósitos"
        subtitle="Lugares de guardado: equipos dados de baja y componentes descartados"
        buttonLabel="Nuevo depósito"
        buttonEvent="openDepositoCrear">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input label="Buscar" wire:model.live.400ms="search" placeholder="Nombre del depósito..." />

                    @if (Auth::user()->hasRole('Administrador'))
                        <x-form.select label="Empresa" placeholder="Todas" :options="$this->empresasOpciones" wire:model.live="empresa_id" />
                    @endif
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button wire:click="$toggle('mostrar_inactivos')" role="switch"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                               border-2 border-transparent transition-colors duration-200
                               {{ $mostrar_inactivos ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                     shadow ring-0 transition duration-200
                                     {{ $mostrar_inactivos ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">Mostrar depósitos desactivados</span>
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    <x-table.crud-table :headers="['Nombre', 'Empresa', 'Ubicación', 'Estado', 'Acciones']" :paginated="null">

        @forelse ($this->depositos as $deposito)
            <tr wire:key="deposito-{{ $deposito->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       {{ ! $deposito->activo ? 'opacity-60 bg-gray-50 dark:bg-cerberus-dark/30' : '' }}
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">
                <td class="px-4 py-3">
                    <p class="text-[#1E293B] dark:text-white font-medium text-sm">{{ $deposito->nombre }}</p>
                    @if ($deposito->descripcion)
                        <p class="text-gray-500 dark:text-cerberus-light text-xs">{{ $deposito->descripcion }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">{{ $deposito->empresa->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">{{ $deposito->ubicacion->nombre ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if ($deposito->activo)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            <span class="material-icons text-xs">block</span> Inactivo
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if ($deposito->activo)
                        <x-table.table-actions :model="$deposito" editEvent="openDepositoEditar">
                            <x-slot name="acciones">
                                <li>
                                    <a href="{{ route('admin.configuracion.depositos.show', $deposito) }}" wire:navigate
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                              hover:text-cerberus-primary dark:hover:text-cerberus-accent
                                              transition-colors duration-100">
                                        <span class="material-icons text-base text-cerberus-primary dark:text-cerberus-accent">visibility</span>
                                        Ver contenido
                                    </a>
                                </li>
                                <li>
                                    <button wire:click="$dispatch('confirmar', {
                                                titulo: 'Desactivar depósito',
                                                mensaje: '¿Desactivar el depósito «{{ $deposito->nombre }}»?',
                                                accionEvento: 'depositoDesactivarConfirmado',
                                                accionParams: [{{ $deposito->id }}],
                                                confirmLabel: 'Desactivar',
                                            })"
                                            @click="close()"
                                            class="flex items-center gap-3 px-4 py-2.5 w-full
                                                   text-gray-600 dark:text-cerberus-light
                                                   hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                                   hover:text-red-600 dark:hover:text-red-400
                                                   transition-colors duration-100">
                                        <span class="material-icons text-base text-red-500">block</span>
                                        Desactivar
                                    </button>
                                </li>
                            </x-slot>
                        </x-table.table-actions>
                    @else
                        <button wire:click="$dispatch('confirmar', {
                                    titulo: 'Reactivar depósito',
                                    mensaje: '¿Reactivar el depósito «{{ $deposito->nombre }}»?',
                                    accionEvento: 'depositoReactivarConfirmado',
                                    accionParams: [{{ $deposito->id }}],
                                    variant: 'primary',
                                    confirmLabel: 'Reactivar',
                                })"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium
                                       bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                       border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">restart_alt</span> Reactivar
                        </button>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-cerberus-steel">
                    No hay depósitos todavía.
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>
