<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('almacen.registrar-entrada-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Solicitudes pendientes', 'value' => $stats['pendientes'],  'icon' => 'pending_actions'],
        ['title' => 'Listas para entregar',   'value' => $stats['entregables'], 'icon' => 'local_shipping'],
        ['title' => 'Casos esperando',        'value' => $stats['casos'],       'icon' => 'build'],
        ['title' => 'Componentes pedidos',    'value' => $stats['componentes'], 'icon' => 'inventory_2'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Solicitudes pendientes"
        subtitle="Componentes que los casos pidieron cuando no había stock. Las más antiguas se atienden primero.">

        <x-slot name="actions">
            <a href="{{ route('admin.almacen.index') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                      bg-white dark:bg-cerberus-mid border border-[#1E40AF] dark:border-cerberus-accent
                      text-[#1E40AF] dark:text-cerberus-accent hover:bg-[#1E40AF]/5 dark:hover:bg-cerberus-primary/10
                      transition-all duration-150 flex-shrink-0">
                <span class="material-icons text-base">warehouse</span>
                Volver al almacén
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">

                @if ($this->activeFiltersCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-cerberus-primary/60 text-white">
                            {{ $this->activeFiltersCount }} filtro(s) activo(s)
                        </span>
                        <button wire:click="resetFilters"
                            class="text-xs text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 flex items-center gap-1 transition">
                            <span class="material-icons text-xs">close</span>
                            Limpiar
                        </button>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input
                        label="Buscar"
                        wire:model.live.500ms="search"
                        placeholder="Componente o código del equipo..."
                    />

                    @if (Auth::user()->hasRole('Administrador'))
                        <x-form.select
                            label="Empresa"
                            placeholder="Todas"
                            :options="$this->empresasOpciones"
                            wire:model.live="empresa_id"
                        />
                    @endif
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button
                        wire:click="$toggle('solo_entregables')"
                        role="switch"
                        aria-checked="{{ $solo_entregables ? 'true' : 'false' }}"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                               border-2 border-transparent transition-colors duration-200
                               {{ $solo_entregables ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                     shadow ring-0 transition duration-200
                                     {{ $solo_entregables ? 'translate-x-4' : 'translate-x-0' }}">
                        </span>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">
                        Solo las que se pueden entregar ya
                    </span>
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Componente', 'Caso', 'Cantidad', 'Pedido', 'Situación', 'Acciones']"
        :paginated="$registros">

        @forelse ($registros as $item)
            @php($caso = $item->mantenimiento)
            <tr wire:key="sol-{{ $item->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30 hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">

                <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->componente->nombre ?? '—' }}</p>
                    @if ($item->componente)
                        <p class="text-xs text-gray-500 dark:text-cerberus-accent">
                            Stock: {{ $item->componente->stock_actual }} {{ $item->componente->unidad }}
                        </p>
                    @endif
                </td>

                <td class="px-4 py-3">
                    <a href="{{ route('admin.mantenimientos.show', $caso) }}" wire:navigate
                       class="text-sm font-medium text-cerberus-primary dark:text-cerberus-accent hover:underline">
                        #{{ $caso->id }} · {{ $caso->equipo->codigo_interno ?? 'Equipo #' . $caso->equipo_id }}
                    </a>
                    <p class="text-xs text-gray-500 dark:text-cerberus-accent">
                        {{ $caso->esPreventivo() ? 'Mantenimiento' : 'Reparación' }} · {{ $caso->estado }}
                        @if (Auth::user()->hasRole('Administrador')) · {{ $caso->empresa->nombre ?? '' }} @endif
                    </p>
                </td>

                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                    {{ $item->cantidad_requerida }}
                </td>

                <td class="px-4 py-3">
                    <p class="text-sm text-gray-900 dark:text-white">{{ $item->created_at->format('d/m/Y') }}</p>
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel">{{ $item->created_at->diffForHumans() }}</p>
                </td>

                <td class="px-4 py-3">
                    @if ($item->entregable)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Lista para entregar
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-400
                                     border border-purple-200 dark:border-purple-500/30">
                            <span class="material-icons text-xs">hourglass_empty</span> Esperando
                        </span>
                        <p class="mt-1 text-xs text-gray-500 dark:text-cerberus-light max-w-xs">{{ $item->motivo_no_entregable }}</p>
                    @endif
                </td>

                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-3">
                        @if ($item->entregable && $item->en_el_caso)
                            @can('update', $caso)
                                <a href="{{ route('admin.mantenimientos.show', $caso) }}" wire:navigate
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg font-medium
                                           bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition"
                                    title="Se elige la pieza por su serial en el caso">
                                    <span class="material-icons text-sm">open_in_new</span> Entregar en el caso
                                </a>
                            @endcan
                        @elseif ($item->entregable)
                            @can('update', $caso)
                                <button wire:click="entregar({{ $item->id }})" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg font-medium
                                           bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition disabled:opacity-60">
                                    <span class="material-icons text-sm">local_shipping</span> Entregar
                                </button>
                            @endcan
                        @elseif ($item->componente)
                            @can('registrarMovimiento', $item->componente)
                                <button wire:click="$dispatch('openRegistrarEntrada', { componenteId: {{ $item->componente->id }} })"
                                    class="inline-flex items-center gap-1 text-xs text-cerberus-primary dark:text-cerberus-accent hover:underline"
                                    title="Registrar la entrada del stock que falta">
                                    <span class="material-icons text-sm">add_box</span> Registrar entrada
                                </button>
                            @endcan
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-4xl block mb-2 opacity-40">task_alt</span>
                    No hay solicitudes pendientes.
                </td>
            </tr>
        @endforelse
    </x-table.crud-table>

</div>
