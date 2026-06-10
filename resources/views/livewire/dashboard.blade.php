<div>
    {{-- ══════════════════════════════════════════════════════════════
         HEADER DE BIENVENIDA
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">
                Bienvenido, {{ auth()->user()->name }} 👋
            </h1>
            <p class="text-cerberus-accent text-sm mt-1">
                @if(auth()->user()->empresaActiva)
                    <span class="material-icons text-xs align-middle mr-1 text-cerberus-light">business</span>
                    {{ auth()->user()->empresaActiva->nombre }}
                    @if(auth()->user()->ubicacion)
                        <span class="mx-2 text-cerberus-steel">·</span>
                        <span class="material-icons text-xs align-middle mr-1 text-cerberus-light">location_on</span>
                        {{ auth()->user()->ubicacion->nombre }}
                    @endif
                @else
                    <span class="material-icons text-xs align-middle mr-1">schedule</span>
                    {{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                @endif
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">

            {{-- ── Selector de empresa (solo Administrador) ──────────────── --}}
            @if($esAdmin)
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="flex items-center gap-2 bg-cerberus-mid border rounded-lg px-3 py-2 text-sm transition-colors
                               {{ $empresaFiltroId
                                  ? 'border-cerberus-light/50 text-cerberus-light'
                                  : 'border-cerberus-steel text-cerberus-accent hover:border-cerberus-light/40 hover:text-cerberus-light' }}">
                    <span class="material-icons text-base">business</span>
                    <span class="max-w-[160px] truncate">
                        {{ $empresaSeleccionada?->nombre ?? 'Todas las empresas' }}
                    </span>
                    <span class="material-icons text-sm" :class="open ? 'rotate-180' : ''" style="transition:transform 200ms">
                        expand_more
                    </span>
                </button>

                <div x-show="open"
                     x-cloak
                     @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-1 w-64 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-xl z-50 overflow-hidden">

                    {{-- Opción: todas --}}
                    <button wire:click="$set('empresaFiltroId', null)"
                            @click="open = false"
                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-left transition-colors
                                   {{ !$empresaFiltroId
                                      ? 'bg-cerberus-light/10 text-cerberus-light font-semibold'
                                      : 'text-cerberus-accent hover:bg-cerberus-darkest/40 hover:text-white' }}">
                        <span class="material-icons text-base">public</span>
                        Todas las empresas
                        @if(!$empresaFiltroId)
                            <span class="material-icons text-sm ml-auto text-cerberus-light">check</span>
                        @endif
                    </button>

                    <div class="border-t border-cerberus-steel/50 max-h-60 overflow-y-auto">
                        @foreach($empresas as $empresa)
                            <button wire:click="$set('empresaFiltroId', {{ $empresa->id }})"
                                    @click="open = false"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-left transition-colors
                                           {{ $empresaFiltroId == $empresa->id
                                              ? 'bg-cerberus-light/10 text-cerberus-light font-semibold'
                                              : 'text-cerberus-accent hover:bg-cerberus-darkest/40 hover:text-white' }}">
                                <span class="material-icons text-base text-cerberus-steel">business</span>
                                <span class="truncate">{{ $empresa->nombre }}</span>
                                @if($empresaFiltroId == $empresa->id)
                                    <span class="material-icons text-sm ml-auto text-cerberus-light">check</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Botón exportar Excel ────────────────────────────────────── --}}
            @unless(auth()->user()->hasRole('Usuario'))
            <a href="{{ route('export.dashboard', array_filter(['empresa_id' => $empresaFiltroId])) }}"
               class="flex items-center gap-2 bg-cerberus-mid border border-cerberus-steel rounded-lg px-3 py-2 text-sm
                      text-cerberus-accent hover:border-emerald-500/40 hover:text-emerald-400 transition-colors"
               title="Exportar indicadores del dashboard a Excel">
                <span class="material-icons text-base">download</span>
                <span class="hidden sm:inline">Exportar</span>
            </a>
            @endunless

            {{-- ── Fecha y alerta vencidos ─────────────────────────────────── --}}
            <div class="text-cerberus-accent text-sm font-mono bg-cerberus-mid border border-cerberus-steel rounded-lg px-4 py-2">
                <span class="material-icons text-xs align-middle mr-1">calendar_today</span>
                {{ now()->format('d/m/Y') }}
                @if($prestamosVencidos > 0)
                    <span class="ml-3 bg-red-500/20 text-red-400 border border-red-500/30 rounded px-2 py-0.5 text-xs font-semibold animate-pulse">
                        ⚠ {{ $prestamosVencidos }} vencido{{ $prestamosVencidos > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TARJETAS DE ESTADÍSTICAS
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-4 mb-6">

        {{-- Equipos Activos --}}
        <a href="{{ route('admin.equipos.index') }}"
           class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-4 shadow-cerberus hover:border-cerberus-light/40 transition-colors group">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-lg bg-blue-500/15">
                    <span class="material-icons text-blue-400 text-xl">devices</span>
                </div>
                <span class="material-icons text-cerberus-steel text-sm group-hover:text-cerberus-light transition-colors">arrow_forward</span>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($totalEquipos) }}</p>
            <p class="text-cerberus-accent text-xs mt-1">Equipos activos</p>
        </a>

        {{-- Equipos Asignados --}}
        <a href="{{ route('admin.asignaciones.index') }}"
           class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-4 shadow-cerberus hover:border-cerberus-light/40 transition-colors group">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-lg bg-teal-500/15">
                    <span class="material-icons text-teal-400 text-xl">assignment_ind</span>
                </div>
                <span class="material-icons text-cerberus-steel text-sm group-hover:text-cerberus-light transition-colors">arrow_forward</span>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($equiposAsignados) }}</p>
            <p class="text-cerberus-accent text-xs mt-1">En asignación</p>
        </a>

        {{-- Préstamos Activos --}}
        <a href="{{ route('admin.prestamos.index') }}"
           class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-4 shadow-cerberus hover:border-cerberus-light/40 transition-colors group">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-lg bg-purple-500/15">
                    <span class="material-icons text-purple-400 text-xl">swap_horiz</span>
                </div>
                <span class="material-icons text-cerberus-steel text-sm group-hover:text-cerberus-light transition-colors">arrow_forward</span>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($prestamosActivos) }}</p>
            <p class="text-cerberus-accent text-xs mt-1">Préstamos activos</p>
        </a>

        {{-- Préstamos Vencidos --}}
        <a href="{{ route('admin.prestamos.index') }}"
           class="bg-cerberus-mid border rounded-xl p-4 shadow-cerberus transition-colors group
               {{ $prestamosVencidos > 0
                   ? 'border-red-500/40 hover:border-red-400/60'
                   : 'border-cerberus-steel hover:border-cerberus-light/40' }}">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-lg {{ $prestamosVencidos > 0 ? 'bg-red-500/20' : 'bg-cerberus-darkest/60' }}">
                    <span class="material-icons text-xl {{ $prestamosVencidos > 0 ? 'text-red-400' : 'text-cerberus-accent' }}">warning</span>
                </div>
                @if($prestamosVencidos > 0)
                    <span class="text-xs bg-red-500/20 text-red-400 border border-red-500/30 rounded px-1.5 py-0.5 font-semibold">!</span>
                @endif
            </div>
            <p class="text-3xl font-bold {{ $prestamosVencidos > 0 ? 'text-red-400' : 'text-white' }}">
                {{ number_format($prestamosVencidos) }}
            </p>
            <p class="text-cerberus-accent text-xs mt-1">Préstamos vencidos</p>
        </a>

        {{-- Traslados del mes --}}
        <a href="{{ route('admin.traslados.index') }}"
           class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-4 shadow-cerberus hover:border-cerberus-light/40 transition-colors group">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-lg bg-orange-500/15">
                    <span class="material-icons text-orange-400 text-xl">local_shipping</span>
                </div>
                <span class="material-icons text-cerberus-steel text-sm group-hover:text-cerberus-light transition-colors">arrow_forward</span>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($trasladosMes) }}</p>
            <p class="text-cerberus-accent text-xs mt-1">Traslados ({{ now()->isoFormat('MMM') }})</p>
        </a>

        {{-- KPI: Edad Promedio de Equipos --}}
        <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-4 shadow-cerberus">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-lg bg-indigo-500/15">
                    <span class="material-icons text-indigo-400 text-xl">schedule</span>
                </div>
                @if($edadPromedioAnios !== null)
                    @php
                        $semaforo = match(true) {
                            $edadPromedioAnios < 2  => ['text-emerald-400', 'Nuevo'],
                            $edadPromedioAnios < 4  => ['text-yellow-400',  'Maduro'],
                            default                 => ['text-red-400',     'Envejecido'],
                        };
                    @endphp
                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded border
                                 {{ $semaforo[0] }} border-current bg-current/10">
                        {{ $semaforo[1] }}
                    </span>
                @endif
            </div>
            @if($edadPromedioAnios !== null)
                <p class="text-3xl font-bold text-white">{{ $edadPromedioAnios }}<span class="text-base font-normal text-cerberus-accent ml-1">años</span></p>
            @else
                <p class="text-3xl font-bold text-cerberus-steel">—</p>
            @endif
            <p class="text-cerberus-accent text-xs mt-1">Edad prom. equipos</p>
        </div>

        {{-- Usuarios Activos --}}
        @unless(auth()->user()->hasRole('Usuario'))
        <a href="{{ route('admin.usuarios.index') }}"
           class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-4 shadow-cerberus hover:border-cerberus-light/40 transition-colors group">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-lg bg-emerald-500/15">
                    <span class="material-icons text-emerald-400 text-xl">people</span>
                </div>
                <span class="material-icons text-cerberus-steel text-sm group-hover:text-cerberus-light transition-colors">arrow_forward</span>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($usuariosActivos) }}</p>
            <p class="text-cerberus-accent text-xs mt-1">Usuarios activos</p>
        </a>
        @endunless

    </div>

    {{-- ══════════════════════════════════════════════════════════════
         GRÁFICAS
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

        {{-- Donut: Equipos por Estado --}}
        <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-5 shadow-cerberus">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-white">Equipos por estado</h2>
                    <p class="text-cerberus-accent text-xs mt-0.5">Distribución actual del inventario</p>
                </div>
                <span class="material-icons text-cerberus-accent">donut_large</span>
            </div>
            @if($equiposPorEstado->isEmpty())
                <div class="flex items-center justify-center h-48 text-cerberus-accent text-sm">Sin datos</div>
            @else
                <div class="flex items-center gap-6">
                    <div class="flex-shrink-0" style="width:180px;height:180px;">
                        <canvas id="chartEstados"></canvas>
                    </div>
                    <div class="flex-1 space-y-2 min-w-0">
                        @foreach($equiposPorEstado as $nombre => $cantidad)
                            @php $pct = $totalEquipos > 0 ? round($cantidad / $totalEquipos * 100) : 0; @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-cerberus-accent truncate pr-2">{{ $nombre }}</span>
                                    <span class="text-white font-semibold flex-shrink-0">{{ $cantidad }}</span>
                                </div>
                                <div class="w-full bg-cerberus-darkest/60 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-cerberus-light transition-all duration-500"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Barras: Equipos por Categoría --}}
        <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl p-5 shadow-cerberus">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-white">Equipos por categoría</h2>
                    <p class="text-cerberus-accent text-xs mt-0.5">Top categorías del inventario</p>
                </div>
                <span class="material-icons text-cerberus-accent">bar_chart</span>
            </div>
            @if($equiposPorCategoria->isEmpty())
                <div class="flex items-center justify-center h-48 text-cerberus-accent text-sm">Sin datos</div>
            @else
                <div class="space-y-3">
                    @php $maxCategoria = $equiposPorCategoria->max(); @endphp
                    @foreach($equiposPorCategoria->take(7) as $nombre => $cantidad)
                        @php $pct = $maxCategoria > 0 ? round($cantidad / $maxCategoria * 100) : 0; @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-cerberus-accent truncate pr-2">{{ $nombre }}</span>
                                <span class="text-white font-semibold flex-shrink-0">{{ $cantidad }}</span>
                            </div>
                            <div class="w-full bg-cerberus-darkest/60 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all duration-500"
                                     style="width: {{ $pct }}%; background: linear-gradient(90deg, #A9D6E5, #5B8FA8);"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════
         ALERTAS + ACTIVIDAD
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- Columna izquierda (2/3): Alertas --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Tabla: Préstamos Vencidos --}}
            <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-cerberus-steel">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-red-400 {{ $prestamosVencidosLista->isEmpty() ? 'opacity-30' : 'animate-pulse' }}"></div>
                        <h2 class="text-base font-semibold text-white">Préstamos vencidos</h2>
                        @if($prestamosVencidosLista->isNotEmpty())
                            <span class="bg-red-500/20 text-red-400 border border-red-500/30 text-xs font-semibold rounded-full px-2 py-0.5">
                                {{ $prestamosVencidosLista->count() }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('admin.prestamos.index') }}"
                       class="text-cerberus-accent hover:text-cerberus-light text-xs flex items-center gap-1 transition-colors">
                        Ver todos <span class="material-icons text-sm">arrow_forward</span>
                    </a>
                </div>

                @if($prestamosVencidosLista->isEmpty())
                    <div class="flex flex-col items-center justify-center py-8 text-cerberus-accent">
                        <span class="material-icons text-4xl mb-2 text-emerald-400/60">check_circle</span>
                        <p class="text-sm">Sin préstamos vencidos</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-cerberus-accent text-xs bg-cerberus-darkest/30">
                                    <th class="px-5 py-2.5 text-left font-medium">Receptor</th>
                                    <th class="px-3 py-2.5 text-left font-medium hidden sm:table-cell">Venció</th>
                                    <th class="px-3 py-2.5 text-center font-medium">Equipos</th>
                                    <th class="px-3 py-2.5 text-center font-medium">Días</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-cerberus-steel/40">
                                @foreach($prestamosVencidosLista as $prestamo)
                                    @php
                                        $dias = now()->diffInDays($prestamo->fecha_devolucion_esperada, false) * -1;
                                    @endphp
                                    <tr class="hover:bg-cerberus-darkest/20 transition-colors">
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
                                                    <span class="material-icons text-red-400 text-sm">person</span>
                                                </div>
                                                <span class="text-white text-sm font-medium truncate max-w-[150px]">
                                                    {{ $prestamo->receptorNombre() }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-cerberus-accent text-xs hidden sm:table-cell whitespace-nowrap">
                                            {{ $prestamo->fecha_devolucion_esperada?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="bg-cerberus-darkest/60 text-cerberus-light text-xs rounded-full px-2 py-0.5 font-semibold">
                                                {{ $prestamo->items_pendientes }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="bg-red-500/20 text-red-400 text-xs font-bold rounded px-2 py-0.5">
                                                +{{ $dias }}d
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('admin.prestamos.devolver', $prestamo) }}"
                                               class="text-cerberus-light hover:text-white text-xs flex items-center justify-end gap-1 transition-colors">
                                                Ver <span class="material-icons text-sm">open_in_new</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Tabla: Garantías por Vencer --}}
            <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-cerberus-steel">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-yellow-400 {{ $garantiasPorVencer->isEmpty() ? 'opacity-30' : 'animate-pulse' }}"></div>
                        <h2 class="text-base font-semibold text-white">Garantías por vencer</h2>
                        <span class="text-cerberus-accent text-xs">(próximos 30 días)</span>
                        @if($garantiasPorVencer->isNotEmpty())
                            <span class="bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 text-xs font-semibold rounded-full px-2 py-0.5">
                                {{ $garantiasPorVencer->count() }}
                            </span>
                        @endif
                    </div>
                     <a href="{{ route('admin.equipos.index') }}"
                       class="text-cerberus-accent hover:text-cerberus-light text-xs flex items-center gap-1 transition-colors">
                        Ver todos <span class="material-icons text-sm">arrow_forward</span>
                    </a>
                </div>

                @if($garantiasPorVencer->isEmpty())
                    <div class="flex flex-col items-center justify-center py-8 text-cerberus-accent">
                        <span class="material-icons text-4xl mb-2 text-emerald-400/60">verified_user</span>
                        <p class="text-sm">Sin garantías próximas a vencer</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-cerberus-accent text-xs bg-cerberus-darkest/30">
                                    <th class="px-5 py-2.5 text-left font-medium">Equipo</th>
                                    <th class="px-3 py-2.5 text-left font-medium hidden sm:table-cell">Categoría</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Vence</th>
                                    <th class="px-3 py-2.5 text-center font-medium">Días rest.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-cerberus-steel/40">
                                @foreach($garantiasPorVencer as $equipo)
                                    @php
                                        $diasRestantes = now()->diffInDays($equipo->fecha_garantia_fin, false);
                                        $urgente = $diasRestantes <= 7;
                                    @endphp
                                    <tr class="hover:bg-cerberus-darkest/20 transition-colors">
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full {{ $urgente ? 'bg-red-500/20' : 'bg-yellow-500/15' }} flex items-center justify-center flex-shrink-0">
                                                    <span class="material-icons text-sm {{ $urgente ? 'text-red-400' : 'text-yellow-400' }}">memory</span>
                                                </div>
                                                <span class="text-white text-sm font-mono">{{ $equipo->codigo_interno }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-cerberus-accent text-xs hidden sm:table-cell">
                                            {{ $equipo->categoria?->nombre ?? '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-cerberus-accent text-xs whitespace-nowrap">
                                            {{ $equipo->fecha_garantia_fin?->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="text-xs font-bold rounded px-2 py-0.5
                                                {{ $urgente
                                                    ? 'bg-red-500/20 text-red-400'
                                                    : 'bg-yellow-500/20 text-yellow-400' }}">
                                                {{ $diasRestantes }}d
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        {{-- Columna derecha (1/3): Actividad Reciente --}}
        <div class="space-y-4">

            {{-- Actividad reciente --}}
            <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-cerberus-steel">
                    <h2 class="text-base font-semibold text-white">Actividad reciente</h2>
                    <span class="material-icons text-cerberus-accent text-sm">history</span>
                </div>

                @if($actividadReciente->isEmpty())
                    <div class="flex flex-col items-center justify-center py-8 text-cerberus-accent">
                        <span class="material-icons text-4xl mb-2">timeline</span>
                        <p class="text-sm">Sin actividad reciente</p>
                    </div>
                @else
                    <div class="divide-y divide-cerberus-steel/40 max-h-[420px] overflow-y-auto">
                        @foreach($actividadReciente as $audit)
                            @php
                                $iconos = [
                                    'created' => ['add_circle', 'text-emerald-400'],
                                    'updated' => ['edit', 'text-blue-400'],
                                    'deleted' => ['delete', 'text-red-400'],
                                ];
                                [$icono, $color] = $iconos[$audit->accion] ?? ['info', 'text-cerberus-accent'];
                                $tablaLabel = match($audit->tabla) {
                                    'equipos'    => 'Equipo',
                                    'asignaciones' => 'Asignación',
                                    'prestamos'  => 'Préstamo',
                                    'traslados'  => 'Traslado',
                                    'users'      => 'Usuario',
                                    default      => ucfirst($audit->tabla),
                                };
                                $accionLabel = match($audit->accion) {
                                    'created' => 'creó',
                                    'updated' => 'editó',
                                    'deleted' => 'eliminó',
                                    default   => $audit->accion,
                                };
                            @endphp
                            <div class="flex items-start gap-3 px-4 py-3 hover:bg-cerberus-darkest/20 transition-colors">
                                <div class="w-7 h-7 rounded-full bg-cerberus-darkest/60 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="material-icons text-sm {{ $color }}">{{ $icono }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-xs leading-snug">
                                        <span class="font-semibold">{{ $audit->usuario?->name ?? 'Sistema' }}</span>
                                        <span class="text-cerberus-accent"> {{ $accionLabel }} </span>
                                        <span class="text-cerberus-light">{{ $tablaLabel }}</span>
                                        <span class="text-cerberus-accent"> #{{ $audit->registro_id }}</span>
                                    </p>
                                    <p class="text-cerberus-steel text-xs mt-0.5">
                                        {{ \Carbon\Carbon::parse($audit->created_at)->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Últimos Traslados --}}
            <div class="bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-cerberus-steel">
                    <h2 class="text-base font-semibold text-white">Últimos traslados</h2>
                    <a href="{{ route('admin.traslados.index') }}"
                       class="text-cerberus-accent hover:text-cerberus-light text-xs flex items-center gap-1 transition-colors">
                        Ver todos <span class="material-icons text-sm">arrow_forward</span>
                    </a>
                </div>

                @if($ultimosTraslados->isEmpty())
                    <div class="flex flex-col items-center justify-center py-6 text-cerberus-accent">
                        <span class="material-icons text-3xl mb-1">local_shipping</span>
                        <p class="text-sm">Sin traslados registrados</p>
                    </div>
                @else
                    <div class="divide-y divide-cerberus-steel/40">
                        @foreach($ultimosTraslados as $traslado)
                            <div class="px-4 py-3 hover:bg-cerberus-darkest/20 transition-colors">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-cerberus-light text-xs font-mono font-semibold">{{ $traslado->numero }}</span>
                                    <span class="text-cerberus-steel text-xs">{{ $traslado->fecha_traslado?->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-xs text-cerberus-accent">
                                    <span class="truncate max-w-[80px]">{{ $traslado->ubicacionOrigen?->nombre ?? '—' }}</span>
                                    <span class="material-icons text-xs text-cerberus-steel flex-shrink-0">arrow_forward</span>
                                    <span class="truncate max-w-[80px]">{{ $traslado->ubicacionDestino?->nombre ?? '—' }}</span>
                                    <span class="ml-auto flex-shrink-0 bg-cerberus-darkest/60 text-cerberus-light text-xs rounded-full px-2 py-0.5">
                                        {{ $traslado->items_count }} eq.
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         CHART.JS — DONUT EQUIPOS POR ESTADO
    ══════════════════════════════════════════════════════════════════ --}}
    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        (function () {
            const estadosLabels = @json($equiposPorEstado->keys());
            const estadosData   = @json($equiposPorEstado->values());

            if (!estadosLabels.length) return;

            const palette = [
                '#A9D6E5', '#5B8FA8', '#1B4F72', '#76D7C4', '#F0B27A',
                '#EC7063', '#A569BD', '#85C1E9', '#58D68D', '#F7DC6F',
            ];

            const canvas = document.getElementById('chartEstados');
            if (!canvas) return;

            // Destruir instancia previa si existe (Livewire re-render)
            const prev = Chart.getChart(canvas);
            if (prev) prev.destroy();

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: estadosLabels,
                    datasets: [{
                        data: estadosData,
                        backgroundColor: palette.slice(0, estadosData.length),
                        borderWidth: 2,
                        borderColor: '#1B263B',
                        hoverBorderColor: '#ffffff30',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.parsed} equipo${ctx.parsed !== 1 ? 's' : ''}`
                            }
                        }
                    },
                },
            });
        })();
    </script>
    @endscript
</div>
