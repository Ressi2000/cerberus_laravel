<div class="space-y-5" wire:poll.{{ $intervalo }}s.keep-alive>
    @php
        $fmtMs  = fn ($v) => $v === null ? '—' : number_format($v, $v < 10 ? 1 : 0) . ' ms';
        $fmtPct = fn ($v) => $v === null ? '—' : number_format($v, 2) . ' %';
        $pct    = fn ($a, $b) => $b ? round($a / $b * 100) : 0;
        $panel  = 'bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel/60 shadow-sm dark:shadow-none rounded-xl p-4';
        $h3     = 'text-sm font-semibold text-[#1E293B] dark:text-white mb-3 flex items-center gap-2';
    @endphp

    {{-- ── Barra superior ────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <p class="text-xs text-gray-500 dark:text-cerberus-accent flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse inline-block"></span>
            Revisión automática cada minuto · pantalla actualizada {{ now()->format('H:i:s') }}
        </p>
        <div class="sm:ml-auto flex items-center gap-2">
            @if ($this->empresasOpciones->isNotEmpty())
                <select wire:model.live="empresa_id"
                        class="text-sm rounded-lg border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white">
                    <option value="">Todas las empresas</option>
                    @foreach ($this->empresasOpciones as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            @endif
            <button wire:click="revisarAhora" wire:loading.attr="disabled" wire:target="revisarAhora"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                           bg-[#1E40AF] hover:bg-[#1E3A8A] text-white shadow-sm transition disabled:opacity-60">
                <span class="material-icons text-base" wire:loading.class="animate-spin" wire:target="revisarAhora">refresh</span>
                Revisar ahora
            </button>
        </div>
    </div>

    @if ($total === 0 && $pausados === 0)
        <div class="{{ $panel }} py-12 text-center">
            <span class="material-icons text-5xl text-gray-300 dark:text-cerberus-steel block mb-2">lan</span>
            <p class="text-gray-600 dark:text-cerberus-light">Aún no hay hosts monitoreados.</p>
            <a href="{{ route('admin.monitor-red.hosts') }}"
               class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-[#1E40AF] dark:text-cerberus-accent hover:underline">
                <span class="material-icons text-base">add</span> Agregar el primero
            </a>
        </div>
    @else

        {{-- ── Alerta: hosts caídos ahora ───────────────────────────────── --}}
        @if ($caidos->isNotEmpty())
            <div class="rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 p-4">
                <p class="text-sm font-semibold text-red-700 dark:text-red-400 flex items-center gap-2 mb-2">
                    <span class="material-icons text-base">wifi_off</span>
                    {{ $caidos->count() }} host(s) sin respuesta
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($caidos as $h)
                        <span wire:key="caido-{{ $h->id }}"
                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs
                                     bg-white dark:bg-cerberus-mid border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-300">
                            <b>{{ $h->nombre }}</b>
                            <span class="text-red-500/80 dark:text-red-400/70">{{ $h->host }}</span>
                            · {{ \App\Models\MonitorCaida::formatearDuracion((int) $h->estado_desde?->diffInSeconds(now(), true)) }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── KPIs ─────────────────────────────────────────────────────── --}}
        @php
            $tituloHosts = $pausados ? 'Hosts (+' . $pausados . ' pausados)' : 'Hosts monitoreados';
        @endphp
        <x-ui.stats-cards :items="[
            ['title' => $tituloHosts, 'value' => $total, 'icon' => 'lan'],
            ['title' => 'Activos · ' . $pct($up, $total) . ' %', 'value' => $up, 'icon' => 'check_circle'],
            ['title' => 'Caídos',             'value' => $dn, 'icon' => 'wifi_off'],
            ['title' => 'Caídas en 24 h',     'value' => $caidasHoy, 'icon' => 'warning'],
            ['title' => 'Latencia promedio', 'value' => $fmtMs($latProm), 'icon' => 'speed'],
            ['title' => 'Disponibilidad 24 h', 'value' => $fmtPct($disp['d1']), 'icon' => 'today'],
            ['title' => 'Disponibilidad 7 días', 'value' => $fmtPct($disp['d7']), 'icon' => 'date_range'],
            ['title' => 'Disponibilidad 30 días', 'value' => $fmtPct($disp['d30']), 'icon' => 'calendar_month'],
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">

            {{-- Estado general --}}
            <div class="{{ $panel }}">
                <h3 class="{{ $h3 }}"><span class="material-icons text-base text-cerberus-accent">donut_large</span>Estado general</h3>
                <div class="flex h-4 rounded-full overflow-hidden bg-gray-100 dark:bg-cerberus-steel/30">
                    <span class="bg-green-500" style="width: {{ $pct($up, $total) }}%"></span>
                    <span class="bg-red-500" style="width: {{ $pct($dn, $total) }}%"></span>
                    <span class="bg-gray-400" style="width: {{ $pct($pendientes, $total) }}%"></span>
                </div>
                <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-500 dark:text-cerberus-light">
                    <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-green-500 inline-block"></i>Activos {{ $pct($up, $total) }} %</span>
                    <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-red-500 inline-block"></i>Caídos {{ $pct($dn, $total) }} %</span>
                    <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-gray-400 inline-block"></i>Pendientes {{ $pct($pendientes, $total) }} %</span>
                </div>
            </div>

            {{-- Latencia por hora --}}
            <div class="{{ $panel }} lg:col-span-1 xl:col-span-2">
                <h3 class="{{ $h3 }}"><span class="material-icons text-base text-cerberus-accent">bar_chart</span>Latencia promedio por hora (24 h)</h3>
                @php $maxMs = max(1, collect($horas)->max('ms') ?? 1); @endphp
                <div class="flex items-end gap-[3px] h-32 pb-4">
                    @foreach ($horas as $f)
                        @php
                            $alto = $f['ms'] === null ? 2 : max(5, $f['ms'] / $maxMs * 100);
                            $tip  = $f['hora']->format('H') . ':00 · ' . ($f['ms'] === null ? 'sin datos' : $fmtMs($f['ms']) . ' · disp. ' . number_format($f['up'], 0) . ' %');
                        @endphp
                        <div class="flex-1 h-full relative flex items-end" title="{{ $tip }}">
                            <span @class([
                                'block w-full rounded-t',
                                'bg-gray-300 dark:bg-cerberus-steel/50' => $f['up'] === null,
                                'bg-amber-500' => $f['up'] !== null && $f['up'] < 100,
                                'bg-[#1E40AF] dark:bg-cerberus-accent' => $f['up'] !== null && $f['up'] >= 100,
                            ]) style="height: {{ $alto }}%"></span>
                            <i class="absolute -bottom-4 inset-x-0 text-center text-[10px] not-italic text-gray-400 dark:text-cerberus-steel">
                                {{ (int) $f['hora']->format('H') % 6 === 0 ? $f['hora']->format('H') . 'h' : '' }}
                            </i>
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-500 dark:text-cerberus-light">
                    <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-[#1E40AF] dark:bg-cerberus-accent inline-block"></i>Sin fallos</span>
                    <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-amber-500 inline-block"></i>Hubo fallos</span>
                    <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-gray-300 dark:bg-cerberus-steel/50 inline-block"></i>Sin datos</span>
                </div>
            </div>

            {{-- Por tipo / Por proveedor --}}
            @foreach (['Disponibilidad por tipo' => [$porTipo, 'category'], 'Por proveedor / enlace' => [$porProveedor, 'router']] as $titulo => [$grupos, $icono])
                <div class="{{ $panel }}">
                    <h3 class="{{ $h3 }}"><span class="material-icons text-base text-cerberus-accent">{{ $icono }}</span>{{ $titulo }}</h3>
                    <div class="space-y-3">
                        @forelse ($grupos as $nombre => $g)
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-700 dark:text-white">{{ $nombre }}</span>
                                    <span class="text-gray-500 dark:text-cerberus-light">{{ $g['up'] }}/{{ $g['n'] }} activos · {{ $pct($g['up'], $g['n']) }} %</span>
                                </div>
                                <div class="h-2 rounded-full overflow-hidden bg-gray-100 dark:bg-cerberus-steel/30">
                                    <span @class(['block h-full', 'bg-amber-500' => $g['dn'] > 0, 'bg-green-500' => $g['dn'] === 0])
                                          style="width: {{ $pct($g['up'], $g['n']) }}%"></span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 dark:text-cerberus-steel">Sin datos.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach

            {{-- Más lentos --}}
            <div class="{{ $panel }}">
                <h3 class="{{ $h3 }}"><span class="material-icons text-base text-cerberus-accent">hourglass_bottom</span>Hosts más lentos ahora</h3>
                @php $maxLento = max(1, $lentos->first()?->latencia_ms ?? 1); @endphp
                <div class="space-y-3">
                    @forelse ($lentos as $h)
                        <div wire:key="lento-{{ $h->id }}">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-700 dark:text-white truncate">{{ $h->nombre }}</span>
                                <span class="text-gray-500 dark:text-cerberus-light">{{ $fmtMs($h->latencia_ms) }}</span>
                            </div>
                            <div class="h-2 rounded-full overflow-hidden bg-gray-100 dark:bg-cerberus-steel/30">
                                <span class="block h-full bg-amber-500" style="width: {{ $h->latencia_ms / $maxLento * 100 }}%"></span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-cerberus-steel">Sin datos de latencia todavía.</p>
                    @endforelse
                </div>
            </div>

            {{-- Más caídas 30 d --}}
            <div class="{{ $panel }}">
                <h3 class="{{ $h3 }}"><span class="material-icons text-base text-cerberus-accent">leaderboard</span>Más caídas (30 días)</h3>
                <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/30 text-sm">
                    @forelse ($topCaidas as $t)
                        <div class="flex justify-between gap-3 py-2">
                            <span class="text-gray-700 dark:text-white truncate">
                                {{ $t->host?->nombre ?? '—' }}
                                <span class="text-xs text-gray-400 dark:text-cerberus-steel">{{ $t->host?->host }}</span>
                            </span>
                            <b class="text-gray-900 dark:text-white">{{ $t->n }}</b>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-cerberus-steel">Sin caídas registradas.</p>
                    @endforelse
                </div>
            </div>

            {{-- Caídas recientes --}}
            <div class="{{ $panel }}">
                <h3 class="{{ $h3 }}">
                    <span class="material-icons text-base text-cerberus-accent">history</span>Caídas recientes
                    <a href="{{ route('admin.monitor-red.caidas') }}" class="ml-auto text-xs font-normal text-[#1E40AF] dark:text-cerberus-accent hover:underline">Ver todo</a>
                </h3>
                <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/30 text-sm">
                    @forelse ($recientes as $c)
                        <div class="flex justify-between gap-3 py-2" wire:key="rec-{{ $c->id }}">
                            <span class="min-w-0">
                                <span class="text-gray-700 dark:text-white block truncate">{{ $c->host?->nombre ?? '—' }}</span>
                                <span class="text-xs text-gray-400 dark:text-cerberus-steel">{{ $c->inicio->format('d/m/Y H:i') }}</span>
                            </span>
                            @if ($c->enCurso())
                                <span class="self-center px-2 py-0.5 text-xs rounded-full bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/30">En curso</span>
                            @else
                                <span class="self-center text-xs text-gray-500 dark:text-cerberus-light whitespace-nowrap">{{ \App\Models\MonitorCaida::formatearDuracion($c->duracion()) }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-cerberus-steel">Sin caídas registradas.</p>
                    @endforelse
                </div>
            </div>

            {{-- Último test de velocidad --}}
            <div class="{{ $panel }}">
                <h3 class="{{ $h3 }}">
                    <span class="material-icons text-base text-cerberus-accent">speed</span>Último test de velocidad
                    <a href="{{ route('admin.monitor-red.velocidad') }}" class="ml-auto text-xs font-normal text-[#1E40AF] dark:text-cerberus-accent hover:underline">Medir</a>
                </h3>
                @if ($ultimoTest)
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div><p class="text-xs text-gray-500 dark:text-cerberus-light">Bajada</p><p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($ultimoTest->bajada_mbps, 1) }} <small class="text-xs font-normal">Mbps</small></p></div>
                        <div><p class="text-xs text-gray-500 dark:text-cerberus-light">Subida</p><p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($ultimoTest->subida_mbps, 1) }} <small class="text-xs font-normal">Mbps</small></p></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-cerberus-light mt-3">
                        {{ $ultimoTest->isp ?: 'Proveedor no identificado' }} · {{ $ultimoTest->created_at->format('d/m/Y H:i') }}
                    </p>
                @else
                    <p class="text-sm text-gray-400 dark:text-cerberus-steel">Aún no hay tests guardados.</p>
                @endif
            </div>
        </div>
    @endif
</div>
