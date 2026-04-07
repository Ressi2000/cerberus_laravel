{{--
    historial-usuario.blade.php
    Tres secciones: equipos activos · timeline · planillas
--}}
<div class="space-y-8">

    {{-- ── CARD DEL USUARIO ────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6">
        <div class="flex items-start gap-4">
            <img src="{{ $this->usuario->foto_url }}"
                 alt="{{ $this->usuario->name }}"
                 class="w-16 h-16 rounded-full object-cover flex-shrink-0
                        border-2 border-gray-100 dark:border-cerberus-steel/50">
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $this->usuario->name }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-1 mt-2">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel">Cédula</p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $this->usuario->cedula ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel">Ficha</p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $this->usuario->ficha ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel">Empresa</p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $this->usuario->empresaNomina?->nombre ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel">Sede</p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $this->usuario->ubicacion?->nombre ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel">Cargo</p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $this->usuario->cargo?->nombre ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel">Departamento</p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $this->usuario->departamento?->nombre ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel">Supervisor</p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $this->usuario->jefe?->name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Accesos rápidos a planillas --}}
            <div class="flex flex-col gap-2 flex-shrink-0">
                <a href="{{ route('admin.asignaciones.planilla.egreso', $this->usuario) }}"
                   target="_blank"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium
                          bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400
                          border border-red-200 dark:border-red-700/40
                          hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                    <span class="material-icons text-sm">logout</span>
                    Planilla de egreso
                </a>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- SECCIÓN 1: EQUIPOS ACTIVOS                                             --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 dark:text-cerberus-steel mb-3 flex items-center gap-2">
            <span class="material-icons text-base text-emerald-500">devices</span>
            Equipos asignados actualmente
            <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
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
                            ->filter(fn($v) => $v->atributo?->visible_en_tabla)
                            ->sortBy(fn($v) => $v->atributo?->orden ?? 99);
                    @endphp
                    <div class="bg-white dark:bg-cerberus-mid
                                border border-gray-200 dark:border-cerberus-steel
                                rounded-xl overflow-hidden">

                        {{-- Equipo principal --}}
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                                            bg-cerberus-primary/10 dark:bg-cerberus-primary/15">
                                    <span class="material-icons text-lg text-cerberus-primary dark:text-cerberus-accent">
                                        laptop
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $item->equipo?->codigo_interno ?? '—' }}
                                        </p>
                                        <span class="text-xs px-2 py-0.5 rounded-full
                                                     bg-gray-100 dark:bg-cerberus-steel/30
                                                     text-gray-600 dark:text-cerberus-light">
                                            {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                        </span>
                                        <span class="text-xs px-2 py-0.5 rounded-full
                                                     bg-emerald-100 dark:bg-emerald-900/30
                                                     text-emerald-700 dark:text-emerald-400">
                                            Activo desde {{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                        </span>
                                    </div>

                                    {{-- Atributos EAV visibles --}}
                                    @if ($atributos && $atributos->isNotEmpty())
                                        <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1.5">
                                            @foreach ($atributos as $av)
                                                <span class="text-xs text-gray-500 dark:text-cerberus-accent">
                                                    <span class="font-medium text-gray-700 dark:text-cerberus-light">
                                                        {{ $av->atributo->nombre }}:
                                                    </span>
                                                    {{ $av->valor }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($item->equipo?->serial)
                                        <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                            S/N: {{ $item->equipo->serial }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Acciones del item --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="{{ route('admin.asignaciones.planilla.asignacion', $item->asignacion) }}"
                                   target="_blank"
                                   class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs
                                          bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50
                                          text-gray-600 dark:text-cerberus-light hover:border-cerberus-primary transition"
                                   title="Planilla de asignación">
                                    <span class="material-icons text-sm">download</span>
                                    Planilla
                                </a>
                            </div>
                        </div>

                        {{-- Periféricos anidados --}}
                        @if ($item->hijos->isNotEmpty())
                            <div class="border-t border-gray-100 dark:border-cerberus-steel/30
                                        bg-gray-50/50 dark:bg-cerberus-dark/30 px-5 py-3 space-y-2">
                                <p class="text-xs font-medium text-gray-400 dark:text-cerberus-steel uppercase tracking-wide mb-2">
                                    Periféricos vinculados
                                </p>
                                @foreach ($item->hijos as $hijo)
                                    @if (!$hijo->devuelto)
                                        <div class="flex items-center gap-3">
                                            <span class="material-icons text-base text-gray-300 dark:text-cerberus-steel/50 ml-3">
                                                subdirectory_arrow_right
                                            </span>
                                            <span class="text-xs font-medium text-gray-700 dark:text-cerberus-light">
                                                {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-cerberus-steel">
                                                {{ $hijo->equipo?->categoria?->nombre ?? '—' }}
                                                @if ($hijo->equipo?->serial)
                                                    · S/N: {{ $hijo->equipo->serial }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- SECCIÓN 2: TIMELINE DE ASIGNACIONES                                    --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 dark:text-cerberus-steel mb-4 flex items-center gap-2">
            <span class="material-icons text-base text-purple-500">history</span>
            Historial de asignaciones
        </h3>

        @if ($this->asignaciones->isEmpty())
            <p class="text-sm text-gray-400 dark:text-cerberus-steel">Sin historial de asignaciones.</p>
        @else
            <div class="relative">
                {{-- Línea vertical del timeline --}}
                <div class="absolute left-5 top-0 bottom-0 w-px bg-gray-200 dark:bg-cerberus-steel/30"></div>

                <div class="space-y-4">
                    @foreach ($this->asignaciones as $asignacion)
                        <div class="relative flex gap-4">
                            {{-- Dot del timeline --}}
                            <div class="relative z-10 flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full
                                        {{ $asignacion->estado === 'Activa'
                                            ? 'bg-emerald-100 dark:bg-emerald-900/30 border-2 border-emerald-400 dark:border-emerald-600'
                                            : 'bg-gray-100 dark:bg-cerberus-steel/20 border-2 border-gray-300 dark:border-cerberus-steel/50' }}">
                                <span class="material-icons text-base
                                             {{ $asignacion->estado === 'Activa' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-cerberus-steel' }}">
                                    {{ $asignacion->estado === 'Activa' ? 'assignment' : 'assignment_turned_in' }}
                                </span>
                            </div>

                            {{-- Tarjeta de asignación --}}
                            <div class="flex-1 bg-white dark:bg-cerberus-mid
                                        border border-gray-200 dark:border-cerberus-steel
                                        rounded-xl overflow-hidden mb-1">

                                {{-- Header de la tarjeta --}}
                                <div class="flex items-center justify-between gap-3 px-5 py-3
                                            border-b border-gray-100 dark:border-cerberus-steel/30">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                        </span>
                                        <x-asignaciones.badge-estado :estado="$asignacion->estado" />
                                        <span class="text-xs text-gray-400 dark:text-cerberus-steel">
                                            {{ $asignacion->empresa?->nombre ?? '—' }} ·
                                            Analista: {{ $asignacion->analista?->name ?? '—' }}
                                        </span>
                                    </div>

                                    {{-- Planillas de esta asignación --}}
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.asignaciones.planilla.asignacion', $asignacion) }}"
                                           target="_blank"
                                           class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs
                                                  bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50
                                                  text-gray-600 dark:text-cerberus-light hover:border-cerberus-primary transition"
                                           title="Descargar planilla de asignación">
                                            <span class="material-icons text-sm">download</span>
                                            Asignación
                                        </a>

                                        @if ($asignacion->itemsDevueltos->count() > 0)
                                            <a href="{{ route('admin.asignaciones.planilla.devolucion', $asignacion) }}"
                                               target="_blank"
                                               class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs
                                                      bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40
                                                      text-amber-700 dark:text-amber-400 hover:bg-amber-100 transition"
                                               title="Descargar planilla de devolución">
                                                <span class="material-icons text-sm">keyboard_return</span>
                                                Devolución
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                {{-- Items de la asignación --}}
                                <div class="px-5 py-3 space-y-2">
                                    @foreach ($asignacion->items as $item)
                                        <div class="flex items-start gap-2">
                                            <span class="material-icons text-base mt-0.5 flex-shrink-0
                                                         {{ $item->devuelto ? 'text-gray-300 dark:text-cerberus-steel/40' : 'text-cerberus-accent' }}">
                                                {{ $item->devuelto ? 'check_box' : 'devices' }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <span class="text-sm {{ $item->devuelto ? 'line-through text-gray-400 dark:text-cerberus-steel' : 'text-gray-800 dark:text-white' }}">
                                                    {{ $item->equipo?->codigo_interno ?? '—' }}
                                                </span>
                                                <span class="text-xs text-gray-400 dark:text-cerberus-steel ml-2">
                                                    {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                                    @if ($item->devuelto && $item->fecha_devolucion)
                                                        · devuelto {{ $item->fecha_devolucion->format('d/m/Y') }}
                                                    @endif
                                                </span>

                                                {{-- Periféricos de este item --}}
                                                @foreach ($item->hijos as $hijo)
                                                    <div class="flex items-center gap-1.5 mt-0.5 ml-4">
                                                        <span class="material-icons text-xs text-gray-300 dark:text-cerberus-steel/40">
                                                            subdirectory_arrow_right
                                                        </span>
                                                        <span class="text-xs {{ $hijo->devuelto ? 'line-through text-gray-300 dark:text-cerberus-steel/40' : 'text-gray-500 dark:text-cerberus-accent' }}">
                                                            {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                                            ({{ $hijo->equipo?->categoria?->nombre ?? '—' }})
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    @if ($asignacion->observaciones)
                                        <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-2 pt-2
                                                   border-t border-gray-100 dark:border-cerberus-steel/20">
                                            <span class="material-icons text-xs align-middle">notes</span>
                                            {{ $asignacion->observaciones }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

</div>