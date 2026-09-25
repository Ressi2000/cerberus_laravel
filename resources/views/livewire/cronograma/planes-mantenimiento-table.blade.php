<div class="space-y-6">

    @livewire('cronograma.plan-mantenimiento-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Al día',      'value' => $this->totalAlDia,    'icon' => 'check_circle'],
        ['title' => 'Próximos',    'value' => $this->totalProximos, 'icon' => 'hourglass_top'],
        ['title' => 'Vencidos',    'value' => $this->totalVencidos, 'icon' => 'error_outline'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Cronograma de Mantenimiento Preventivo"
        subtitle="Un plan por categoría de equipos, por empresa: cada cuánto le toca revisión y con qué checklist"
        buttonLabel="Nuevo plan"
        buttonEvent="openPlanCrear">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">

                @if ($this->activeFiltersCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-cerberus-primary/60 text-white">
                            {{ $this->activeFiltersCount }} filtro(s) activo(s)
                        </span>
                        <button wire:click="resetFilters"
                            class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                            <span class="material-icons text-xs">close</span>
                            Limpiar
                        </button>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.select label="Categoría" placeholder="Todas" :options="$this->categoriasOpciones" wire:model.live="categoria_id" />

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
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">Mostrar planes desactivados</span>
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Categoría', 'Empresa', 'Frecuencia', 'Próxima fecha', 'Estado', 'Lote actual', 'Acciones']"
        :paginated="$this->planes">

        @forelse ($this->planes as $plan)
            <tr wire:key="plan-{{ $plan->id }}"
                class="border-b border-gray-100 dark:border-cerberus-steel/30
                       {{ ! $plan->activo ? 'opacity-60 bg-gray-50 dark:bg-cerberus-dark/30' : '' }}
                       hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">
                <td class="px-4 py-3">
                    <p class="text-[#1E293B] dark:text-white font-medium text-sm">{{ $plan->categoria->nombre ?? '—' }}</p>
                    <p class="text-gray-500 dark:text-cerberus-light text-xs">{{ $plan->equiposAlcanzados()->count() }} equipo(s) activo(s)</p>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">{{ $plan->empresa->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">Cada {{ $plan->frecuencia_meses }} {{ $plan->frecuencia_meses == 1 ? 'mes' : 'meses' }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm whitespace-nowrap">
                    {{ $plan->fecha_proximo->format('d/m/Y') }}
                    @if ($plan->duracion_dias_estimada > 1)
                        <span class="block text-xs text-gray-400 dark:text-cerberus-steel">
                            hasta {{ $plan->fechaFinEstimada()->format('d/m/Y') }} ({{ $plan->duracion_dias_estimada }} días)
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if (! $plan->activo)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light
                                     border border-gray-200 dark:border-cerberus-steel/30">
                            Desactivado
                        </span>
                    @elseif ($plan->estaVencido())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400
                                     border border-red-200 dark:border-red-500/30">
                            <span class="material-icons text-xs">error_outline</span> Vencido
                        </span>
                    @elseif ($plan->estaProximo())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400
                                     border border-amber-200 dark:border-amber-500/30">
                            <span class="material-icons text-xs">hourglass_top</span> Próximo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                     bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                     border border-green-200 dark:border-green-500/30">
                            <span class="material-icons text-xs">check_circle</span> Al día
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @php $progreso = $plan->progresoLoteActual(); @endphp
                    @if ($progreso)
                        <a href="{{ route('admin.cronograma.lotes.show', $plan) }}"
                           class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full font-medium hover:underline
                                     {{ $progreso['completados'] === $progreso['total']
                                         ? 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30'
                                         : 'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' }}">
                            {{ $progreso['completados'] }}/{{ $progreso['total'] }} completados
                        </a>
                    @else
                        <span class="text-gray-400 dark:text-cerberus-steel text-xs">Sin generar todavía</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <x-table.table-actions :model="$plan" editEvent="openPlanEditar" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-cerberus-steel">
                    No hay planes de mantenimiento todavía.
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>
