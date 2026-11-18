<div class="space-y-6">

    @if (auth()->user()->hasRole('Administrador'))
        @livewire('equipos.criterios-renovacion-modal')
    @endif

    @php
        $tarjetas = [
            'obsoleto'      => ['Obsoletos',      'block',          'text-red-500 dark:text-red-400',       'border-red-300 dark:border-red-500/50'],
            'repotenciable' => ['Repotenciables', 'upgrade',        'text-blue-600 dark:text-blue-400',     'border-blue-300 dark:border-blue-500/50'],
            'revisar'       => ['Por revisar',    'visibility',     'text-amber-600 dark:text-amber-400',   'border-amber-300 dark:border-amber-500/50'],
            'sano'          => ['Sanos',          'check_circle',   'text-green-600 dark:text-green-400',   'border-green-300 dark:border-green-500/50'],
        ];
        $badge = [
            'obsoleto'      => 'bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/30',
            'repotenciable' => 'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30',
            'revisar'       => 'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30',
            'sano'          => 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
            'sin_datos'     => 'bg-gray-100 dark:bg-cerberus-steel/20 text-gray-600 dark:text-cerberus-light border-gray-200 dark:border-cerberus-steel/40',
        ];
    @endphp

    {{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Renovación de equipos</h1>
            <p class="text-sm text-gray-500 dark:text-cerberus-light">
                Qué conviene reemplazar, mejorar o vigilar, según reglas claras por tipo de equipo. Cada equipo dice por qué.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $this->urlExportar() }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                      bg-white dark:bg-cerberus-mid border border-[#1E40AF] dark:border-cerberus-accent
                      text-[#1E40AF] dark:text-cerberus-accent hover:bg-[#1E40AF]/5 transition">
                <span class="material-icons text-base">download</span> Excel
            </a>
            @if (auth()->user()->hasRole('Administrador'))
                <button wire:click="$dispatch('openCriteriosRenovacion', { categoriaId: {{ $categoria_id ?: 'null' }} })"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition">
                    <span class="material-icons text-base">tune</span> Criterios
                </button>
            @endif
        </div>
    </div>

    {{-- ── Conteos (clic = filtrar) ────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach ($tarjetas as $clave => [$titulo, $icono, $color, $borde])
            <button type="button" wire:click="verClasificacion('{{ $clave }}')"
                @class([
                    'text-left bg-white dark:bg-cerberus-mid border rounded-xl p-4 shadow-sm transition hover:shadow',
                    $borde . ' ring-2 ring-offset-0 ring-current' => $clasificacion === $clave,
                    'border-gray-200 dark:border-cerberus-steel' => $clasificacion !== $clave,
                ])>
                <div class="flex items-center gap-2 mb-1">
                    <span class="material-icons text-xl {{ $color }}">{{ $icono }}</span>
                    <span class="text-xs text-gray-500 dark:text-cerberus-light">{{ $titulo }}</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->totales[$clave] }}</p>
            </button>
        @endforeach
        <button type="button" wire:click="$toggle('incompletos')"
            @class([
                'text-left bg-white dark:bg-cerberus-mid border rounded-xl p-4 shadow-sm transition hover:shadow',
                'border-gray-400 dark:border-cerberus-light ring-2 ring-gray-300' => $incompletos,
                'border-gray-200 dark:border-cerberus-steel' => ! $incompletos,
            ])
            title="Equipos a los que les falta información en la ficha (procesador, RAM, disco, fecha…)">
            <div class="flex items-center gap-2 mb-1">
                <span class="material-icons text-xl text-gray-500 dark:text-cerberus-light">rule</span>
                <span class="text-xs text-gray-500 dark:text-cerberus-light">Fichas incompletas</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->totales['incompletos'] }}</p>
        </button>
    </div>

    {{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-form.input label="Buscar" wire:model.live.400ms="search" placeholder="Código, procesador o sistema…" />
            @if (auth()->user()->hasRole('Administrador'))
                <x-form.select label="Empresa" placeholder="Todas" :options="$this->empresasOpciones" wire:model.live="empresa_id" />
            @endif
            <x-form.select label="Tipo de equipo" placeholder="Todos" :options="$this->categoriasOpciones" wire:model.live="categoria_id" />
            <x-form.select label="Clasificación" placeholder="Todas" :options="\App\Services\RenovacionService::CLASIFICACIONES" wire:model.live="clasificacion" />
        </div>
    </div>

    {{-- ── Lista ───────────────────────────────────────────────────────────── --}}
    <div class="space-y-3">
        @forelse ($resultados as $r)
            @php $e = $r['equipo']; $res = $r['resultado']; $d = $res['datos']; @endphp
            <div wire:key="ren-{{ $e->id }}" x-data="{ abierto: false }"
                 class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl">
                <button type="button" @click="abierto = !abierto" class="w-full text-left px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
                    <div class="w-36">
                        <a href="{{ route('admin.equipos.show', $e) }}" wire:navigate @click.stop
                           class="font-semibold text-cerberus-primary dark:text-cerberus-accent hover:underline">{{ $e->codigo_interno }}</a>
                        <p class="text-xs text-gray-500 dark:text-cerberus-light">{{ $e->categoria->nombre ?? '' }} · {{ $e->empresa->nombre ?? '' }}</p>
                    </div>
                    <div class="flex-1 min-w-[16rem] grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-1 text-xs">
                        <div><span class="text-gray-400 dark:text-cerberus-steel">Procesador</span><p class="text-gray-900 dark:text-white truncate" title="{{ $d['procesador_texto'] }}">{{ $d['procesador'] ?? '—' }}</p></div>
                        <div><span class="text-gray-400 dark:text-cerberus-steel">RAM</span><p class="text-gray-900 dark:text-white">{{ $d['ram'] ?? '—' }}</p></div>
                        <div><span class="text-gray-400 dark:text-cerberus-steel">Disco</span><p class="text-gray-900 dark:text-white truncate">{{ $d['discos'] ?? '—' }}</p></div>
                        <div><span class="text-gray-400 dark:text-cerberus-steel">Sistema · Edad</span>
                            <p class="text-gray-900 dark:text-white">{{ $d['so'] ?? '—' }} · {{ $d['edad'] !== null ? $d['edad'] . ' a' . ($d['edad_estimada'] ? ' (est.)' : '') : '—' }}</p></div>
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        @if ($res['faltan'])
                            <span class="material-icons text-base text-gray-400" title="Ficha incompleta">rule</span>
                        @endif
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full border whitespace-nowrap {{ $badge[$res['clasificacion']] }}">
                            {{ \App\Services\RenovacionService::CLASIFICACIONES[$res['clasificacion']] }}
                        </span>
                        <span class="material-icons text-gray-400 transition" :class="abierto && 'rotate-180'">expand_more</span>
                    </div>
                </button>

                {{-- Primer motivo siempre visible; el detalle completo al abrir --}}
                @php $primero = $res['obsoleto'][0] ?? $res['mejoras'][0] ?? $res['revisar'][0] ?? null; @endphp
                @if ($primero)
                    <p x-show="!abierto" class="px-4 pb-3 -mt-1 text-xs text-gray-600 dark:text-cerberus-light truncate">{{ $primero }}</p>
                @endif

                <div x-show="abierto" x-cloak class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm border-t border-gray-100 dark:border-cerberus-steel/40 pt-3">
                    @foreach ([
                        'obsoleto' => ['Por qué conviene reemplazarlo', 'text-red-700 dark:text-red-300', 'block'],
                        'mejoras'  => ['Mejoras recomendadas', 'text-blue-700 dark:text-blue-300', 'upgrade'],
                        'revisar'  => ['A vigilar', 'text-amber-700 dark:text-amber-300', 'visibility'],
                        'faltan'   => ['Falta en la ficha', 'text-gray-600 dark:text-cerberus-light', 'rule'],
                        'info'     => ['Además', 'text-gray-600 dark:text-cerberus-light', 'info'],
                    ] as $clave => [$titulo, $color, $icono])
                        @if ($res[$clave])
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide mb-1 {{ $color }} flex items-center gap-1">
                                    <span class="material-icons text-sm">{{ $icono }}</span>{{ $titulo }}
                                </p>
                                <ul class="list-disc ms-5 space-y-0.5 text-gray-700 dark:text-white">
                                    @foreach ($res[$clave] as $t) <li>{{ $t }}</li> @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center text-sm text-gray-400 dark:text-cerberus-steel py-12 bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl">
                No hay equipos con estos filtros.
            </div>
        @endforelse
    </div>

    <div>{{ $resultados->links() }}</div>
</div>
