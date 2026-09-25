@php
    $plan = $this->plan;
    $casos = $this->casos;
    $progreso = $plan->progresoLoteActual();
    $coloresEstado = [
        'Programado' => 'bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-500/30',
        'En proceso' => 'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30',
        'Completado' => 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
        'Cancelado'  => 'bg-gray-50 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light border-gray-200 dark:border-cerberus-steel/30',
    ];
@endphp

<div class="space-y-6">

    @if ($plan->trashed())
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg text-sm
                    bg-gray-50 dark:bg-cerberus-dark/40 border border-gray-200 dark:border-cerberus-steel
                    text-gray-600 dark:text-cerberus-light">
            <span class="material-icons text-base">info</span>
            Este plan fue eliminado — no se generarán lotes nuevos, pero puedes seguir procesando los casos de este historial.
        </div>
    @endif

    {{-- ── HEADER DEL LOTE ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">inventory_2</span>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $plan->categoria->nombre }} — {{ $plan->empresa->nombre }}
                    </h2>
                    <p class="text-gray-500 dark:text-cerberus-light text-sm mt-0.5">
                        Cada {{ $plan->frecuencia_meses }} {{ $plan->frecuencia_meses == 1 ? 'mes' : 'meses' }} ·
                        {{ $plan->fecha_proximo->format('d/m/Y') }}
                        @if ($plan->duracion_dias_estimada > 1)
                            hasta {{ $plan->fechaFinEstimada()->format('d/m/Y') }} ({{ $plan->duracion_dias_estimada }} días)
                        @endif
                    </p>
                </div>
            </div>

            @if ($progreso)
                <div class="text-right">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $progreso['completados'] }}/{{ $progreso['total'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-cerberus-light">casos completados</p>
                </div>
            @endif
        </div>

        @if ($progreso)
            <div class="mt-4 w-full h-2 bg-gray-100 dark:bg-cerberus-dark rounded-full overflow-hidden">
                <div class="h-full bg-green-500 transition-all"
                     style="width: {{ $progreso['total'] > 0 ? round(100 * $progreso['completados'] / $progreso['total']) : 0 }}%"></div>
            </div>
        @endif
    </div>

    @if ($casos->isEmpty())
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-10 text-center">
            <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel mb-2">event_busy</span>
            <p class="text-sm text-gray-500 dark:text-cerberus-light">
                Este plan todavía no generó su lote de casos — se genera automáticamente
                {{ \App\Models\PlanMantenimiento::DIAS_ANTELACION_GENERACION }} días antes de la fecha próxima.
            </p>
        </div>
    @else
        {{-- ── BARRA DE ACCIONES MASIVAS ───────────────────────────────────────── --}}
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4
                    flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-cerberus-light cursor-pointer select-none">
                <input type="checkbox"
                    @change="$wire.toggleTodos($event.target.checked)"
                    :checked="{{ count($seleccionados) }} === {{ $casos->count() }} && {{ $casos->count() }} > 0"
                    class="rounded border-gray-300 dark:border-cerberus-steel text-cerberus-primary focus:ring-cerberus-primary/30">
                Seleccionar todos
            </label>

            <span class="text-xs text-gray-400 dark:text-cerberus-steel">{{ count($seleccionados) }} seleccionado(s)</span>

            <div class="flex items-center gap-2 ml-2">
                <label class="text-xs text-gray-500 dark:text-cerberus-light">Agrupar por</label>
                <select wire:model.live="agruparPor"
                    class="text-sm rounded-lg px-2 py-1.5
                           bg-white dark:bg-cerberus-dark border border-gray-300 dark:border-cerberus-steel
                           text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30">
                    <option value="equipo">Equipo</option>
                    <option value="usuario">Usuario</option>
                    <option value="departamento">Departamento</option>
                </select>
            </div>

            <div class="flex-1"></div>

            <button wire:click="avanzarSeleccionados"
                wire:confirm="¿Avanzar los casos «Programado» seleccionados a «En proceso»? Esto bloquea sus equipos."
                class="px-3 py-1.5 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition flex items-center gap-1.5">
                <span class="material-icons text-sm">arrow_forward</span> Avanzar seleccionados
            </button>

            <button wire:click="completarSeleccionados"
                wire:confirm="¿Completar los casos «En proceso» seleccionados? Esto libera sus equipos."
                class="px-3 py-1.5 text-sm rounded-lg bg-green-600 hover:bg-green-700 text-white transition flex items-center gap-1.5">
                <span class="material-icons text-sm">check_circle</span> Completar seleccionados
            </button>
        </div>

        {{-- ── TABLA DE CASOS DEL LOTE ─────────────────────────────────────────── --}}
        <x-table.crud-table :headers="['', 'Equipo', 'Estado', 'Checklist', '', 'Acciones']" :paginated="null">
            @foreach ($this->gruposCasos as $nombreGrupo => $casosGrupo)
                @if ($nombreGrupo !== null)
                    <tr wire:key="lote-grupo-{{ Str::slug($nombreGrupo) }}" class="bg-gray-50 dark:bg-cerberus-dark/40">
                        <td colspan="6" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-accent">
                            <span class="material-icons text-sm align-middle mr-1">{{ $agruparPor === 'departamento' ? 'apartment' : 'person' }}</span>
                            {{ $nombreGrupo }} <span class="font-normal normal-case text-gray-400">({{ $casosGrupo->count() }})</span>
                        </td>
                    </tr>
                @endif

                @foreach ($casosGrupo as $caso)
                    @php
                        $totalTareas = count($caso->checklist ?? []);
                        $hechas = collect($caso->checklist ?? [])->filter(fn ($i) => $i['hecho'] ?? false)->count();
                    @endphp
                    <tr wire:key="lote-caso-{{ $caso->id }}"
                        class="border-b border-gray-100 dark:border-cerberus-steel/30 hover:bg-gray-50 dark:hover:bg-cerberus-dark/30 transition-colors">
                        <td class="px-4 py-3">
                            <input type="checkbox" wire:model="seleccionados" value="{{ $caso->id }}"
                                class="rounded border-gray-300 dark:border-cerberus-steel text-cerberus-primary focus:ring-cerberus-primary/30">
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-[#1E293B] dark:text-white font-medium text-sm font-mono">{{ $caso->equipo->codigo_interno ?? '—' }}</p>
                            <p class="text-gray-500 dark:text-cerberus-light text-xs">{{ $caso->equipo->categoria->nombre ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span @class(['inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full border font-medium', $coloresEstado[$caso->estado] ?? 'bg-gray-50 text-gray-500 border-gray-200'])>
                                {{ $caso->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-cerberus-light text-xs">
                            @if ($totalTareas > 0)
                                {{ $hechas }}/{{ $totalTareas }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($caso->en_garantia)
                                <span class="material-icons text-sm text-blue-500" title="En garantía">verified_user</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($caso->estado === 'En proceso')
                                    <button wire:click="$dispatch('openReportarProblema', { mantenimientoId: {{ $caso->id }} })"
                                        class="text-amber-600 dark:text-amber-400 hover:underline text-xs flex items-center gap-1">
                                        <span class="material-icons text-sm">report_problem</span> Reportar
                                    </button>
                                @endif
                                <a href="{{ route('admin.mantenimientos.show', $caso) }}"
                                   class="text-cerberus-primary dark:text-cerberus-accent hover:underline text-xs flex items-center gap-1">
                                    Ver <span class="material-icons text-sm">open_in_new</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </x-table.crud-table>
    @endif

</div>
