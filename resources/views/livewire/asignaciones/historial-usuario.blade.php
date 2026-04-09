{{--
    historial-usuario.blade.php v2
    ─────────────────────────────────────────────────────────────────────────
    Sección 0 → Stats del usuario (nuevo)
    Sección 1 → Equipos activos actuales
    Sección 2 → Timeline de asignaciones (con filtros + paginación + acordeón)
    ─────────────────────────────────────────────────────────────────────────
--}}
<div class="space-y-6">

    {{-- ══════════════════════════════════════════════════════════════════════
         TARJETA DE PERFIL + ACCESOS RÁPIDOS
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">

            {{-- Foto + datos --}}
            <div class="flex items-center gap-4">
                <img src="{{ $this->usuario->foto_url }}"
                     alt="{{ $this->usuario->name }}"
                     class="w-14 h-14 rounded-full object-cover border-2 border-cerberus-primary/30 flex-shrink-0">
                <div>
                    <p class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $this->usuario->name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-0.5">
                        {{ $this->usuario->cargo?->nombre ?? '—' }}
                        · {{ $this->usuario->departamento?->nombre ?? '—' }}
                        · Ficha: {{ $this->usuario->ficha ?? '—' }}
                    </p>
                    <div class="flex gap-3 mt-2 flex-wrap">
                        <span class="text-xs text-gray-400 dark:text-cerberus-steel flex items-center gap-1">
                            <span class="material-icons text-xs">business</span>
                            {{ $this->usuario->empresaNomina?->nombre ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-cerberus-steel flex items-center gap-1">
                            <span class="material-icons text-xs">location_on</span>
                            {{ $this->usuario->ubicacion?->nombre ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-cerberus-steel flex items-center gap-1">
                            <span class="material-icons text-xs">supervisor_account</span>
                            {{ $this->usuario->jefe?->name ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Planilla de egreso --}}
            <a href="{{ route('admin.asignaciones.planilla.egreso', $this->usuario) }}"
               target="_blank"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium
                      bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400
                      border border-red-200 dark:border-red-700/40
                      hover:bg-red-100 dark:hover:bg-red-900/40 transition flex-shrink-0">
                <span class="material-icons text-sm">logout</span>
                Planilla de egreso
            </a>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 0: STATS DEL USUARIO (nueva — punto D)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-3 gap-4">

        <div class="bg-white dark:bg-cerberus-mid
                    border border-gray-200 dark:border-cerberus-steel
                    rounded-xl px-5 py-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ $this->statsUsuario['equipos_activos'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-1">Equipos activos</p>
        </div>

        <div class="bg-white dark:bg-cerberus-mid
                    border border-gray-200 dark:border-cerberus-steel
                    rounded-xl px-5 py-4 text-center">
            <p class="text-2xl font-bold text-cerberus-primary dark:text-cerberus-accent">
                {{ $this->statsUsuario['total_asignaciones'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-1">Asignaciones históricas</p>
        </div>

        <div class="bg-white dark:bg-cerberus-mid
                    border border-gray-200 dark:border-cerberus-steel
                    rounded-xl px-5 py-4 text-center">
            <p class="text-base font-bold text-gray-700 dark:text-cerberus-light">
                {{ $this->statsUsuario['ultima_asignacion'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-1">Última asignación</p>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 1: EQUIPOS ACTIVOS
    ══════════════════════════════════════════════════════════════════════ --}}
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-wider
                   text-gray-400 dark:text-cerberus-steel mb-3
                   flex items-center gap-2">
            <span class="material-icons text-base text-emerald-500">devices</span>
            Equipos asignados actualmente
            <span class="px-2 py-0.5 rounded-full text-xs
                         bg-emerald-100 dark:bg-emerald-900/30
                         text-emerald-700 dark:text-emerald-400">
                {{ $this->equiposActivos->count() }}
            </span>
        </h3>

        @if ($this->equiposActivos->isEmpty())
            <div class="flex items-center gap-3 px-5 py-4 rounded-xl
                        bg-gray-50 dark:bg-cerberus-dark/50
                        border border-dashed border-gray-200 dark:border-cerberus-steel/40
                        text-gray-400 dark:text-cerberus-steel text-sm">
                <span class="material-icons text-2xl opacity-40">inbox</span>
                Sin equipos activos en este momento.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->equiposActivos as $item)
                    @php
                        $atributos = $item->equipo?->atributosActuales
                            ->filter(fn ($v) => $v->atributo?->visible_en_tabla)
                            ->sortBy(fn ($v) => $v->atributo?->orden ?? 99)
                            ->take(4);
                    @endphp
                    <div class="bg-white dark:bg-cerberus-mid
                                border border-gray-200 dark:border-cerberus-steel
                                rounded-xl overflow-hidden">
                        <div class="flex items-start justify-between gap-3 px-5 py-3">
                            <div class="flex items-start gap-2 min-w-0">
                                <span class="material-icons text-base text-cerberus-accent mt-0.5 flex-shrink-0">
                                    devices
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $item->equipo?->codigo_interno ?? '—' }}
                                        <span class="font-normal text-gray-400 dark:text-cerberus-steel">
                                            · {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                        {{ $item->equipo?->nombre_maquina ?? '—' }}
                                        @if ($item->equipo?->serial)
                                            · S/N: {{ $item->equipo->serial }}
                                        @endif
                                    </p>
                                    @if ($atributos && $atributos->isNotEmpty())
                                        <p class="text-xs text-gray-400 dark:text-cerberus-steel/70 mt-0.5">
                                            {{ $atributos->map(fn ($v) => $v->atributo->nombre . ': ' . $v->valor)->implode(' · ') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0
                                         bg-emerald-100 dark:bg-emerald-900/30
                                         text-emerald-700 dark:text-emerald-400">
                                {{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>

                        {{-- Periféricos --}}
                        @if ($item->hijos->where('devuelto', false)->isNotEmpty())
                            <div class="border-t border-gray-100 dark:border-cerberus-steel/30
                                        px-5 py-2 space-y-1 bg-gray-50 dark:bg-cerberus-dark/50">
                                @foreach ($item->hijos->where('devuelto', false) as $hijo)
                                    <p class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1.5">
                                        <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                        {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                        · {{ $hijo->equipo?->categoria?->nombre ?? '—' }}
                                        @if ($hijo->equipo?->serial) · S/N: {{ $hijo->equipo->serial }} @endif
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 2: TIMELINE DE ASIGNACIONES (con filtros + paginación)
    ══════════════════════════════════════════════════════════════════════ --}}
    <section>
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">

            <h3 class="text-sm font-semibold uppercase tracking-wider
                       text-gray-400 dark:text-cerberus-steel flex items-center gap-2">
                <span class="material-icons text-base text-purple-500">history</span>
                Historial de asignaciones
            </h3>

            {{-- Filtros del timeline --}}
            <div class="flex items-center gap-2 flex-wrap">

                {{-- Filtro estado --}}
                <div class="flex rounded-lg border border-gray-200 dark:border-cerberus-steel/50 overflow-hidden text-xs">
                    @foreach (['todas' => 'Todas', 'activa' => 'Activas', 'cerrada' => 'Cerradas'] as $val => $label)
                        <button wire:click="$set('filtroEstado', '{{ $val }}')"
                                class="{{ $filtroEstado === $val
                                    ? 'bg-cerberus-primary text-white'
                                    : 'bg-white dark:bg-cerberus-dark text-gray-600 dark:text-cerberus-light hover:bg-gray-50 dark:hover:bg-cerberus-steel/20' }}
                                       px-3 py-1.5 font-medium transition-colors duration-100 border-r
                                       border-gray-200 dark:border-cerberus-steel/50 last:border-0">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Filtro año --}}
                @if (count($this->aniosDisponibles) > 1)
                    <select wire:model.live="filtroAnio"
                            class="text-xs rounded-lg px-2.5 py-1.5
                                   bg-white dark:bg-cerberus-dark
                                   border border-gray-200 dark:border-cerberus-steel/50
                                   text-gray-700 dark:text-cerberus-light
                                   focus:border-cerberus-primary transition">
                        <option value="">Todos los años</option>
                        @foreach ($this->aniosDisponibles as $anio)
                            <option value="{{ $anio }}">{{ $anio }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- Limpiar --}}
                @if ($filtroEstado !== 'todas' || $filtroAnio)
                    <button wire:click="resetFiltros"
                            class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                        <span class="material-icons text-xs">close</span>
                        Limpiar
                    </button>
                @endif

            </div>
        </div>

        @if ($this->asignaciones->isEmpty())
            <div class="flex items-center gap-3 px-5 py-4 rounded-xl
                        bg-gray-50 dark:bg-cerberus-dark/50
                        border border-dashed border-gray-200 dark:border-cerberus-steel/40
                        text-gray-400 dark:text-cerberus-steel text-sm">
                <span class="material-icons text-2xl opacity-40">history</span>
                Sin asignaciones para los filtros seleccionados.
            </div>
        @else

            {{-- Timeline --}}
            <div class="relative">
                <div class="absolute left-5 top-0 bottom-0 w-px bg-gray-200 dark:bg-cerberus-steel/30"></div>

                <div class="space-y-4">
                    @foreach ($this->asignaciones as $asignacion)

                        {{--
                            Alpine x-data local: controla si la asignación cerrada
                            está expandida o colapsada.
                            Las Activas arrancan abiertas (open: true).
                            Las Cerradas arrancan cerradas (open: false).
                        --}}
                        <div wire:key="hist-{{ $asignacion->id }}"
                             x-data="{ open: {{ $asignacion->estado === 'Activa' ? 'true' : 'false' }} }"
                             class="relative flex gap-4">

                            {{-- Dot del timeline --}}
                            <div class="relative z-10 flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full
                                        {{ $asignacion->estado === 'Activa'
                                            ? 'bg-emerald-100 dark:bg-emerald-900/30 border-2 border-emerald-400 dark:border-emerald-600'
                                            : 'bg-gray-100 dark:bg-cerberus-steel/20 border-2 border-gray-300 dark:border-cerberus-steel/50' }}">
                                <span class="material-icons text-base
                                             {{ $asignacion->estado === 'Activa' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-cerberus-steel' }}">
                                    {{ $asignacion->estado === 'Activa' ? 'devices' : 'lock' }}
                                </span>
                            </div>

                            {{-- Card de la asignación --}}
                            <div class="flex-1 min-w-0 bg-white dark:bg-cerberus-mid
                                        border border-gray-200 dark:border-cerberus-steel
                                        rounded-xl overflow-hidden mb-1">

                                {{-- Header del card — siempre visible, clickeable para colapsar --}}
                                <button type="button"
                                        @click="open = !open"
                                        class="w-full flex items-center justify-between gap-3 px-5 py-3
                                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition text-left">

                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-asignaciones.badge-estado :estado="$asignacion->estado" />
                                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-cerberus-steel hidden sm:block">
                                            {{ $asignacion->empresa?->nombre ?? '—' }}
                                            · Analista: {{ $asignacion->analista?->name ?? '—' }}
                                        </span>
                                        <span class="text-xs text-gray-300 dark:text-cerberus-steel/50">
                                            {{ $asignacion->items->count() }} equipo(s)
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        {{-- Botones planillas --}}
                                        <a href="{{ route('admin.asignaciones.planilla.asignacion', $asignacion) }}"
                                           target="_blank"
                                           @click.stop
                                           title="Planilla de asignación"
                                           class="p-1 rounded text-gray-400 hover:text-cerberus-primary dark:hover:text-cerberus-accent transition">
                                            <span class="material-icons text-sm">download</span>
                                        </a>

                                        @if ($asignacion->itemsDevueltos->count() > 0)
                                            <a href="{{ route('admin.asignaciones.planilla.devolucion', $asignacion) }}"
                                               target="_blank"
                                               @click.stop
                                               title="Planilla de devolución"
                                               class="p-1 rounded text-amber-400 hover:text-amber-500 transition">
                                                <span class="material-icons text-sm">keyboard_return</span>
                                            </a>
                                        @endif

                                        {{-- Chevron accordion --}}
                                        <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel transition-transform duration-200"
                                              :class="open ? 'rotate-180' : ''">
                                            expand_more
                                        </span>
                                    </div>
                                </button>

                                {{-- Cuerpo colapsable — items de la asignación --}}
                                <div x-show="open" x-collapse
                                     class="border-t border-gray-100 dark:border-cerberus-steel/30
                                            px-5 py-3 space-y-2">

                                    @foreach ($asignacion->items as $item)
                                        @php $esPeriférico = $item->equipo_padre_id !== null; @endphp
                                        <div class="flex items-start gap-2
                                                    {{ $esPeriférico ? 'ml-5 mt-0' : '' }}">

                                            {{-- Icono: periférico con subdirectory, principal con devices/check --}}
                                            <span class="material-icons text-base mt-0.5 flex-shrink-0
                                                         {{ $item->devuelto
                                                             ? 'text-gray-300 dark:text-cerberus-steel/40'
                                                             : ($esPeriférico ? 'text-cerberus-primary/40 dark:text-cerberus-accent/50' : 'text-emerald-500 dark:text-emerald-400') }}">
                                                {{ $esPeriférico
                                                    ? 'subdirectory_arrow_right'
                                                    : ($item->devuelto ? 'check_circle' : 'devices') }}
                                            </span>

                                            <div class="min-w-0">
                                                <p class="text-sm
                                                           {{ $item->devuelto
                                                               ? 'text-gray-400 dark:text-cerberus-steel line-through'
                                                               : ($esPeriférico ? 'text-gray-600 dark:text-cerberus-light' : 'text-gray-900 dark:text-white') }}">
                                                    {{ $item->equipo?->codigo_interno ?? '—' }}
                                                    <span class="font-normal text-gray-400 dark:text-cerberus-steel text-xs">
                                                        · {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                                    </span>
                                                    @if ($esPeriférico)
                                                        <span class="text-xs text-cerberus-primary/50 dark:text-cerberus-accent/50">
                                                            (periférico)
                                                        </span>
                                                    @endif
                                                </p>
                                                @if ($item->devuelto)
                                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                                                        Dev. {{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}
                                                        @if ($item->devueltoPor) · {{ $item->devueltoPor->name }} @endif
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                </div>

                            </div>
                        </div>

                    @endforeach
                </div>
            </div>

            {{-- Paginación del timeline --}}
            @if ($this->asignaciones->hasPages())
                <div class="mt-4 flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                        Mostrando {{ $this->asignaciones->firstItem() }}–{{ $this->asignaciones->lastItem() }}
                        de {{ $this->asignaciones->total() }} asignaciones
                    </p>
                    {{ $this->asignaciones->links('vendor.livewire.cerberus-pagination') }}
                </div>
            @endif

        @endif

    </section>

</div>