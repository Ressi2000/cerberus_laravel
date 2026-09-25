@php
    $m = $this->mantenimiento;
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

<div class="space-y-6">

    @livewire('mantenimientos.mantenimiento-baja-modal')

    {{-- ── HEADER ──────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">{{ $m->esCorrectivo() ? 'construction' : 'build' }}</span>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $m->esCorrectivo() ? 'Reparación' : 'Mantenimiento' }} — {{ $m->equipo->codigo_interno ?? '—' }}
                    </h2>
                    <p class="text-gray-500 dark:text-cerberus-light text-sm mt-0.5">
                        {{ $m->equipo->categoria->nombre ?? '—' }} · {{ $m->empresa->nombre ?? '—' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span @class(['inline-flex items-center gap-1 px-3 py-1 text-sm rounded-full border font-medium', $coloresEstado[$m->estado] ?? 'bg-gray-50 text-gray-500 border-gray-200'])>
                    {{ $m->estado }}
                </span>
                @if ($m->esperando_componente)
                    <span class="inline-flex items-center gap-1 px-3 py-1 text-sm rounded-full
                                 bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-400
                                 border border-purple-200 dark:border-purple-500/30">
                        <span class="material-icons text-sm">hourglass_empty</span> Esperando repuesto
                    </span>
                @endif
                @if ($m->en_garantia)
                    <span class="inline-flex items-center gap-1 px-3 py-1 text-sm rounded-full
                                 bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400
                                 border border-blue-200 dark:border-blue-500/30"
                          title="{{ $m->equipo?->fecha_garantia_fin ? 'Vence el ' . $m->equipo->fecha_garantia_fin->format('d/m/Y') : '' }}">
                        <span class="material-icons text-sm">verified_user</span> En garantía
                    </span>
                @endif
            </div>
        </div>

        {{-- Receptor al momento del hallazgo --}}
        @if ($m->asignacion && $m->asignacion->usuario)
            <div class="mt-4 flex items-center gap-2 text-sm text-gray-600 dark:text-cerberus-light bg-gray-50 dark:bg-cerberus-dark/40 rounded-lg px-4 py-2.5">
                <span class="material-icons text-base text-cerberus-accent">person</span>
                Equipo asignado a <strong class="text-gray-900 dark:text-white">{{ $m->asignacion->usuario->name }}</strong> al momento del hallazgo.
            </div>
        @endif

        @if ($m->mantenimientoOrigen)
            <div class="mt-2 flex items-center gap-2 text-sm text-gray-500 dark:text-cerberus-light">
                <span class="material-icons text-base">link</span>
                Originado por el mantenimiento
                <a href="{{ route('admin.mantenimientos.show', $m->mantenimientoOrigen) }}" class="text-cerberus-primary dark:text-cerberus-accent hover:underline">#{{ $m->mantenimientoOrigen->id }}</a>
            </div>
        @endif

        {{-- Datos clave --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5">
            <div class="bg-gray-50 dark:bg-cerberus-dark rounded-lg p-3 border border-gray-100 dark:border-cerberus-steel/40">
                <p class="text-xs text-gray-500 dark:text-cerberus-light mb-1">Reportado por</p>
                <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $m->reportadoPor->name ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-cerberus-dark rounded-lg p-3 border border-gray-100 dark:border-cerberus-steel/40">
                <p class="text-xs text-gray-500 dark:text-cerberus-light mb-1">Responsable</p>
                <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $m->responsable->name ?? $m->proveedor_externo ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-cerberus-dark rounded-lg p-3 border border-gray-100 dark:border-cerberus-steel/40">
                <p class="text-xs text-gray-500 dark:text-cerberus-light mb-1">Inicio</p>
                <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $m->fecha_inicio?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-cerberus-dark rounded-lg p-3 border border-gray-100 dark:border-cerberus-steel/40">
                <p class="text-xs text-gray-500 dark:text-cerberus-light mb-1">Estimada / Real</p>
                <p class="font-semibold text-sm text-gray-900 dark:text-white">
                    {{ $m->fecha_fin_estimada?->format('d/m/Y') ?? '—' }} / {{ $m->fecha_fin_real?->format('d/m/Y') ?? '—' }}
                </p>
            </div>
        </div>

        @if ($m->esCorrectivo() && $m->falla_reportada)
            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-cerberus-light mb-1">Falla reportada</p>
                <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $m->falla_reportada }}</p>
            </div>
        @endif

        @if ($m->esPreventivo() && $m->descripcion)
            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-cerberus-light mb-1">Descripción</p>
                <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $m->descripcion }}</p>
            </div>
        @endif

        @if ($m->estado === 'Dado de baja')
            <div class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40 rounded-lg px-4 py-3 text-sm">
                <p class="text-red-700 dark:text-red-300 font-medium">Motivo de la baja</p>
                <p class="text-red-600 dark:text-red-400 mt-1">{{ $m->motivo_baja }}</p>
                <p class="text-xs text-red-500 dark:text-red-400/70 mt-2">
                    Aprobado por {{ $m->aprobadoPor->name ?? '—' }} el {{ $m->fecha_baja?->format('d/m/Y') }}
                </p>
            </div>
        @endif
    </div>

    {{-- ── CHECKLIST (solo Preventivo) ──────────────────────────────────────── --}}
    @if ($m->esPreventivo() && ! empty($m->checklist))
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1 flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-base">checklist</span>
                Checklist de la revisión
            </h3>
            @php
                $totalTareas = count($m->checklist);
                $hechas = collect($m->checklist)->filter(fn ($i) => $i['hecho'] ?? false)->count();
            @endphp
            <p class="text-xs text-gray-400 dark:text-cerberus-steel mb-3">{{ $hechas }} de {{ $totalTareas }} completadas</p>

            <div class="space-y-1.5">
                @foreach ($m->checklist as $i => $item)
                    <div wire:key="det-checklist-{{ $i }}" class="flex items-center gap-2">
                        @if ($m->estaAbierto())
                            <button wire:click="toggleChecklistItem({{ $i }})"
                                class="flex-shrink-0 w-5 h-5 rounded border flex items-center justify-center transition
                                       {{ ($item['hecho'] ?? false)
                                           ? 'bg-green-600 border-green-600 text-white'
                                           : 'border-gray-300 dark:border-cerberus-steel' }}">
                                @if ($item['hecho'] ?? false)
                                    <span class="material-icons text-xs">check</span>
                                @endif
                            </button>
                        @else
                            <span class="flex-shrink-0 w-5 h-5 rounded border flex items-center justify-center
                                       {{ ($item['hecho'] ?? false)
                                           ? 'bg-green-600 border-green-600 text-white'
                                           : 'border-gray-300 dark:border-cerberus-steel opacity-50' }}">
                                @if ($item['hecho'] ?? false)
                                    <span class="material-icons text-xs">check</span>
                                @endif
                            </span>
                        @endif
                        <span class="text-sm {{ ($item['hecho'] ?? false) ? 'text-gray-500 dark:text-cerberus-light line-through' : 'text-gray-700 dark:text-white' }}">
                            {{ $item['tarea'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── ACCIONES DE FLUJO ──────────────────────────────────────────────── --}}
    @can('update', $m)
        @if ($m->estaAbierto())
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Acciones</h3>
                <div class="flex flex-wrap gap-2">
                    @if (isset(\App\Models\Mantenimiento::SIGUIENTE_ESTADO_SIMPLE[$m->estado]))
                        <button wire:click="avanzarEstado" class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition flex items-center gap-1.5">
                            <span class="material-icons text-sm">arrow_forward</span>
                            Avanzar a «{{ \App\Models\Mantenimiento::SIGUIENTE_ESTADO_SIMPLE[$m->estado] }}»
                        </button>
                    @endif

                    @if ($m->esPreventivo() && $m->estado === 'En proceso')
                        <button wire:click="completar" class="px-4 py-2 text-sm rounded-lg bg-green-600 hover:bg-green-700 text-white transition flex items-center gap-1.5">
                            <span class="material-icons text-sm">check_circle</span> Completar
                        </button>
                    @endif

                    @if ($m->esPreventivo() && in_array($m->estado, ['Programado', 'En proceso']))
                        <button wire:click="cancelar" wire:confirm="¿Cancelar este mantenimiento?" class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white transition flex items-center gap-1.5">
                            <span class="material-icons text-sm">cancel</span> Cancelar
                        </button>
                    @endif

                    @if ($m->esCorrectivo() && $m->estado === 'En reparación')
                        <button wire:click="marcarReparado" class="px-4 py-2 text-sm rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center gap-1.5">
                            <span class="material-icons text-sm">check</span> Marcar reparado
                        </button>
                    @endif

                    @if ($m->esCorrectivo() && $m->estado === 'Reparado')
                        <button wire:click="cerrar" class="px-4 py-2 text-sm rounded-lg bg-green-600 hover:bg-green-700 text-white transition flex items-center gap-1.5">
                            <span class="material-icons text-sm">check_circle</span> Cerrar caso
                        </button>
                    @endif

                    @can('aprobarBaja', $m)
                        @if ($m->esCorrectivo() && in_array($m->estado, ['Diagnosticado', 'En reparación']))
                            <button wire:click="$dispatch('openMantenimientoBaja', { mantenimientoId: {{ $m->id }} })"
                                class="px-4 py-2 text-sm rounded-lg bg-red-600/10 hover:bg-red-600/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-700/40 transition flex items-center gap-1.5">
                                <span class="material-icons text-sm">report</span> Dar de baja (sin solución)
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        @endif
    @endcan

    {{-- ── DIAGNÓSTICO / COSTO ────────────────────────────────────────────── --}}
    {{-- Editable solo mientras el caso está abierto. Cerrado/Completado/Cancelado/
         Dado de baja: se muestra en modo lectura, incluso para Administrador —
         el diagnóstico de un caso ya cerrado no debe poder seguir cambiando. --}}
    @if ($m->estaAbierto())
        @can('update', $m)
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="material-icons text-cerberus-accent text-base">fact_check</span>
                    Diagnóstico
                </h3>

                <div class="space-y-4">
                    @if ($m->esCorrectivo())
                        <x-form.textarea label="Diagnóstico" wire:model="diagnostico" rows="2" placeholder="Resultado del análisis técnico..." />
                        <x-form.textarea label="Causa raíz" wire:model="causa_raiz" rows="2" placeholder="Por qué ocurrió la falla..." />
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form.input label="Costo" type="number" wire:model="costo" placeholder="0.00" suffix="$" />
                    </div>

                    <x-form.textarea label="Observaciones" wire:model="observaciones" rows="2" placeholder="Notas adicionales..." />

                    <div class="flex justify-end">
                        <button wire:click="guardarDiagnostico" class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition">
                            Guardar diagnóstico
                        </button>
                    </div>
                </div>
            </div>
        @endcan
    @elseif ($m->diagnostico || $m->causa_raiz || $m->costo || $m->observaciones)
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-base">fact_check</span>
                Diagnóstico
                <span class="text-xs font-normal text-gray-400 dark:text-cerberus-steel">(caso cerrado, solo lectura)</span>
            </h3>
            <div class="space-y-3 text-sm">
                @if ($m->diagnostico)
                    <div><p class="text-xs text-gray-500 dark:text-cerberus-light mb-0.5">Diagnóstico</p><p class="text-gray-700 dark:text-cerberus-light">{{ $m->diagnostico }}</p></div>
                @endif
                @if ($m->causa_raiz)
                    <div><p class="text-xs text-gray-500 dark:text-cerberus-light mb-0.5">Causa raíz</p><p class="text-gray-700 dark:text-cerberus-light">{{ $m->causa_raiz }}</p></div>
                @endif
                @if ($m->costo)
                    <div><p class="text-xs text-gray-500 dark:text-cerberus-light mb-0.5">Costo</p><p class="text-gray-700 dark:text-cerberus-light">${{ number_format($m->costo, 2) }}</p></div>
                @endif
                @if ($m->observaciones)
                    <div><p class="text-xs text-gray-500 dark:text-cerberus-light mb-0.5">Observaciones</p><p class="text-gray-700 dark:text-cerberus-light">{{ $m->observaciones }}</p></div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── COMPONENTES ─────────────────────────────────────────────────────── --}}
    @livewire('mantenimientos.mantenimiento-componentes-panel', ['mantenimientoId' => $m->id], key('comp-' . $m->id))

    {{-- ── EVIDENCIA FOTOGRÁFICA ───────────────────────────────────────────── --}}
    @livewire('mantenimientos.mantenimiento-evidencias-panel', ['mantenimientoId' => $m->id], key('evi-' . $m->id))

</div>
