<div class="space-y-6">

    @php
        $coloresEstado = [
            'Programado'     => 'bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-500/30',
            'Reportado'      => 'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30',
            'Diagnosticado'  => 'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30',
            'En proceso'     => 'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30',
            'En reparación'  => 'bg-orange-50 dark:bg-orange-500/15 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/30',
            'Reparado'       => 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30',
            'Completado'     => 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
            'Cerrado'        => 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
            'Cancelado'      => 'bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light border-gray-200 dark:border-cerberus-steel/30',
            'Dado de baja'   => 'bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/30',
        ];
    @endphp

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Casos abiertos',      'value' => $this->totalAbiertos,             'icon' => 'pending_actions'],
        ['title' => 'Mantenimientos',      'value' => $this->totalPreventivos,          'icon' => 'build'],
        ['title' => 'Reparaciones',        'value' => $this->totalCorrectivos,          'icon' => 'construction'],
        ['title' => 'Esperando repuesto',  'value' => $this->totalEsperandoComponente,  'icon' => 'hourglass_empty'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Mantenimientos / Reparación"
        subtitle="Trazabilidad de mantenimiento preventivo y reparación correctiva"
        buttonLabel="Nuevo caso"
        :buttonUrl="route('admin.mantenimientos.create')">

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

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-form.input label="Buscar" wire:model.live.400ms="search" placeholder="Código de equipo..." />

                    @if (Auth::user()->hasRole('Administrador'))
                        <x-form.select label="Empresa" placeholder="Todas" :options="$this->empresasOpciones" wire:model.live="empresa_id" />
                    @endif

                    <x-form.select label="Tipo" placeholder="Todos"
                        :options="['Preventivo' => 'Mantenimiento', 'Correctivo' => 'Reparación']"
                        wire:model.live="tipo" />

                    <x-form.select label="Estado" placeholder="Todos"
                        :options="collect(\App\Models\Mantenimiento::ESTADOS_PREVENTIVO)
                            ->merge(\App\Models\Mantenimiento::ESTADOS_CORRECTIVO)
                            ->unique()->mapWithKeys(fn ($e) => [$e => $e])"
                        wire:model.live="estado" />
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button wire:click="$toggle('mostrar_cerrados')" role="switch"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                               border-2 border-transparent transition-colors duration-200
                               {{ $mostrar_cerrados ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                     shadow ring-0 transition duration-200
                                     {{ $mostrar_cerrados ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">Mostrar casos cerrados</span>
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
    <x-table.crud-table
        :headers="['Equipo', 'Empresa', 'Tipo', 'Estado', 'Responsable', 'Inicio', 'Acciones']"
        :paginated="$this->mantenimientos">

        @forelse ($this->mantenimientos as $m)
            <tr wire:key="mant-{{ $m->id }}" class="border-b border-gray-100 dark:border-cerberus-steel/30 hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">
                <td class="px-4 py-3">
                    <p class="text-[#1E293B] dark:text-white font-medium text-sm">{{ $m->equipo->codigo_interno ?? '—' }}</p>
                    <p class="text-gray-500 dark:text-cerberus-light text-xs">{{ $m->equipo->categoria->nombre ?? '—' }}</p>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">{{ $m->empresa->nombre ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1 text-xs font-medium
                                 {{ $m->esCorrectivo() ? 'text-orange-600 dark:text-orange-400' : 'text-sky-600 dark:text-sky-400' }}">
                        <span class="material-icons text-sm">{{ $m->esCorrectivo() ? 'construction' : 'build' }}</span>
                        {{ $m->esCorrectivo() ? 'Reparación' : 'Mantenimiento' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span @class(['inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full border font-medium', $coloresEstado[$m->estado] ?? 'bg-gray-50 text-gray-500 border-gray-200'])>
                        {{ $m->estado }}
                    </span>
                    @if ($m->esperando_componente)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] rounded-full
                                     bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-400
                                     border border-purple-200 dark:border-purple-500/30 mt-1">
                            <span class="material-icons text-[10px]">hourglass_empty</span>
                            Esperando repuesto
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm">
                    {{ $m->responsable->name ?? $m->proveedor_externo ?? '—' }}
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-sm whitespace-nowrap">
                    {{ $m->fecha_inicio?->format('d/m/Y') ?? '—' }}
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.mantenimientos.show', $m) }}"
                       class="inline-flex items-center gap-1 text-cerberus-primary dark:text-cerberus-accent hover:underline text-xs font-medium">
                        Ver <span class="material-icons text-sm">chevron_right</span>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-cerberus-steel">
                    No se encontraron casos.
                </td>
            </tr>
        @endforelse

    </x-table.crud-table>

</div>
