<div class="space-y-6">

    <x-ui.stats-cards :items="[
        ['title' => 'Caídas en curso',        'value' => $this->stats['en_curso'], 'icon' => 'wifi_off'],
        ['title' => 'Caídas en 24 h',         'value' => $this->stats['d1'],       'icon' => 'today'],
        ['title' => 'Caídas en 30 días',      'value' => $this->stats['d30'],      'icon' => 'date_range'],
        ['title' => 'Duración media (30 d)',  'value' => $this->stats['promedio'] ? \App\Models\MonitorCaida::formatearDuracion($this->stats['promedio']) : '—', 'icon' => 'timer'],
    ]" />

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-[#1E293B] dark:text-white">Historial de caídas</h1>
            <p class="text-sm text-gray-500 dark:text-cerberus-accent mt-0.5">Cada intervalo en que un host dejó de responder (se conserva aunque el host se quite del monitoreo)</p>
        </div>
        <a href="{{ $exportarUrl }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold flex-shrink-0
                  bg-white dark:bg-cerberus-mid border border-[#1E40AF] dark:border-cerberus-accent
                  text-[#1E40AF] dark:text-cerberus-accent hover:bg-[#1E40AF]/5 dark:hover:bg-cerberus-primary/10 transition">
            <span class="material-icons text-base">download</span>
            Exportar a Excel
        </a>
    </div>

    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel shadow-sm dark:shadow-cerberus rounded-xl p-4 space-y-4">
        @if ($this->activeFiltersCount > 0)
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 text-xs rounded-full bg-cerberus-primary/60 text-white">{{ $this->activeFiltersCount }} filtro(s) activo(s)</span>
                <button wire:click="resetFilters" class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                    <span class="material-icons text-xs">close</span> Limpiar
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 {{ $this->empresasOpciones->isNotEmpty() ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4">
            <x-form.input label="Buscar" wire:model.live.500ms="search" placeholder="Host, IP o proveedor..." />
            <x-form.select label="Host" placeholder="Todos" wire:model.live="host_id" :options="$this->hostsOpciones" />
            <x-form.input label="Desde" type="date" wire:model.live="desde" />
            <x-form.input label="Hasta" type="date" wire:model.live="hasta" />
            @if ($this->empresasOpciones->isNotEmpty())
                <x-form.select label="Empresa" placeholder="Todas" wire:model.live="empresa_id" :options="$this->empresasOpciones" />
            @endif
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-cerberus-light select-none">
            <input type="checkbox" wire:model.live="en_curso" class="rounded border-gray-300 dark:border-cerberus-steel">
            Solo caídas en curso
        </label>
    </div>

    <x-table.crud-table :headers="['Host', 'IP / DNS', 'Proveedor', 'Inicio de la caída', 'Recuperado', 'Duración']" :paginated="$caidas">
        @forelse ($caidas as $c)
            <tr wire:key="caida-{{ $c->id }}" class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">
                <td class="px-4 py-3 text-sm">
                    <p class="text-gray-900 dark:text-white font-medium">{{ $c->host?->nombre ?? '—' }}</p>
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                        {{ $c->host?->tipo }}
                        @if ($this->empresasOpciones->isNotEmpty()) · {{ $c->host?->empresa?->nombre }} @endif
                        @if ($c->host?->trashed()) · <span class="italic">quitado del monitoreo</span> @endif
                    </p>
                </td>
                <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-cerberus-light">{{ $c->host?->host }}</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">{{ $c->host?->proveedor ?: '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light whitespace-nowrap">{{ $c->inicio->format('d/m/Y H:i:s') }}</td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">
                    @if ($c->enCurso())
                        <span class="px-2 py-0.5 text-xs rounded-full bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/30">En curso</span>
                    @else
                        <span class="text-gray-600 dark:text-cerberus-light">{{ $c->fin->format('d/m/Y H:i:s') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">{{ \App\Models\MonitorCaida::formatearDuracion($c->duracion()) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-4xl block mb-2 opacity-40">check_circle</span>
                    Sin caídas registradas.
                </td>
            </tr>
        @endforelse
    </x-table.crud-table>
</div>
