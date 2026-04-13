{{--
    asignacion-view-modal.blade.php v3
    ─────────────────────────────────────────────────────────────────────────
    Cambios v3:
    - Modal más ancho (max-w-4xl) y más alto (max-h-[92vh]) para ver muchos equipos
    - Agrega ubicación física del equipo en cada tarjeta de equipo activo
    - Empresa en badges en la cabecera del usuario
    - Sede eliminada como campo suelto → integrada en los badges superiores
    ─────────────────────────────────────────────────────────────────────────
--}}
<div>
    @if ($open && $this->usuario)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                 wire:click="cerrar"></div>

            {{-- Modal — más ancho y más alto --}}
            <div class="relative z-50 w-full max-w-4xl bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel
                        rounded-2xl shadow-2xl flex flex-col max-h-[92vh]">

                {{-- ── Header ──────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-6 py-4
                            border-b border-gray-100 dark:border-cerberus-steel/50 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <img src="{{ $this->usuario->foto_url }}"
                             alt="{{ $this->usuario->name }}"
                             class="w-10 h-10 rounded-full object-cover
                                    border-2 border-cerberus-primary/30 flex-shrink-0">
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ $this->usuario->name }}
                            </h2>
                            <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                                {{ $this->usuario->cargo?->nombre ?? '—' }}
                                · Ficha: {{ $this->usuario->ficha ?? '—' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="cerrar"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- ── Cuerpo scrollable ────────────────────────────────────── --}}
                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                    {{-- Tarjeta info del usuario --}}
                    <div class="bg-gray-50 dark:bg-cerberus-dark/60
                                border border-gray-200 dark:border-cerberus-steel/40
                                rounded-xl px-5 py-4">

                        {{-- Badges de empresa y sede --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            @if ($this->usuario->empresaNomina)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                                             bg-blue-100 dark:bg-blue-900/30
                                             text-blue-700 dark:text-blue-300
                                             border border-blue-200 dark:border-blue-700/40">
                                    <span class="material-icons text-xs">business</span>
                                    {{ $this->usuario->empresaNomina->nombre }}
                                </span>
                            @endif
                            @if ($this->usuario->ubicacion)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                                             bg-teal-100 dark:bg-teal-900/30
                                             text-teal-700 dark:text-teal-300
                                             border border-teal-200 dark:border-teal-700/40">
                                    <span class="material-icons text-xs">location_on</span>
                                    {{ $this->usuario->ubicacion->nombre }}
                                </span>
                            @endif
                            @if ($this->usuario->departamento)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                                             bg-purple-100 dark:bg-purple-900/30
                                             text-purple-700 dark:text-purple-300
                                             border border-purple-200 dark:border-purple-700/40">
                                    <span class="material-icons text-xs">domain</span>
                                    {{ $this->usuario->departamento->nombre }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3">
                            <div>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel">Cédula</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $this->usuario->cedula ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel">Ficha</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $this->usuario->ficha ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel">Supervisor</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $this->usuario->jefe?->name ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-cerberus-steel/30">

                    {{-- Equipos activos --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold uppercase tracking-wider
                                       text-gray-400 dark:text-cerberus-steel flex items-center gap-1.5">
                                <span class="material-icons text-sm text-emerald-500">devices</span>
                                Equipos asignados actualmente
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                         bg-emerald-100 dark:bg-emerald-900/30
                                         text-emerald-700 dark:text-emerald-400">
                                {{ $this->equiposActivos->count() }} equipo(s)
                            </span>
                        </div>

                        @if ($this->equiposActivos->isEmpty())
                            <div class="flex flex-col items-center justify-center py-10
                                        bg-gray-50 dark:bg-cerberus-dark/50
                                        border border-dashed border-gray-200 dark:border-cerberus-steel/40
                                        rounded-xl">
                                <span class="material-icons text-4xl text-gray-200 dark:text-cerberus-steel/30 mb-2">
                                    inventory_2
                                </span>
                                <p class="text-sm text-gray-400 dark:text-cerberus-steel">
                                    Sin equipos asignados en este momento.
                                </p>
                            </div>
                        @else
                            {{-- Grid de 2 columnas para aprovechar el ancho extra del modal --}}
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                @foreach ($this->equiposActivos as $item)
                                    @php
                                        $atributos = $item->equipo?->atributosActuales
                                            ->filter(fn ($v) => $v->atributo?->visible_en_tabla)
                                            ->sortBy(fn ($v) => $v->atributo?->orden ?? 99)
                                            ->take(3);
                                    @endphp

                                    <div class="rounded-xl border border-gray-200 dark:border-cerberus-steel/40
                                                bg-white dark:bg-cerberus-dark overflow-hidden">

                                        {{-- Equipo principal --}}
                                        <div class="px-4 py-3">
                                            <div class="flex items-start gap-2 min-w-0">
                                                <span class="material-icons text-base text-cerberus-accent mt-0.5 flex-shrink-0">
                                                    {{ match(true) {
                                                        str_contains(strtolower($item->equipo?->categoria?->nombre ?? ''), 'laptop')   => 'laptop',
                                                        str_contains(strtolower($item->equipo?->categoria?->nombre ?? ''), 'desktop')  => 'desktop_windows',
                                                        str_contains(strtolower($item->equipo?->categoria?->nombre ?? ''), 'monitor')  => 'monitor',
                                                        str_contains(strtolower($item->equipo?->categoria?->nombre ?? ''), 'impresor') => 'print',
                                                        str_contains(strtolower($item->equipo?->categoria?->nombre ?? ''), 'tel')      => 'smartphone',
                                                        str_contains(strtolower($item->equipo?->categoria?->nombre ?? ''), 'switch')   => 'router',
                                                        str_contains(strtolower($item->equipo?->categoria?->nombre ?? ''), 'servidor') => 'dns',
                                                        default => 'devices',
                                                    } }}
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                        {{ $item->equipo?->codigo_interno ?? '—' }}
                                                        <span class="font-normal text-gray-400 dark:text-cerberus-steel text-xs ml-1">
                                                            {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                                        </span>
                                                    </p>

                                                    @if ($item->equipo?->nombre_maquina || $item->equipo?->serial)
                                                        <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                                            {{ $item->equipo?->nombre_maquina ?? '' }}
                                                            @if ($item->equipo?->serial)
                                                                @if ($item->equipo?->nombre_maquina) · @endif
                                                                S/N: {{ $item->equipo->serial }}
                                                            @endif
                                                        </p>
                                                    @endif

                                                    {{-- Atributos EAV visibles --}}
                                                    @if ($atributos && $atributos->isNotEmpty())
                                                        <p class="text-xs text-gray-400 dark:text-cerberus-steel/70 mt-1">
                                                            {{ $atributos->map(fn ($v) => $v->atributo?->nombre . ': ' . $v->valor)->implode(' · ') }}
                                                        </p>
                                                    @endif

                                                    {{-- Ubicación física del equipo --}}
                                                    @if ($item->asignacion?->empresa)
                                                        <span class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 rounded-full text-xs
                                                                     bg-gray-100 dark:bg-cerberus-steel/20
                                                                     text-gray-500 dark:text-cerberus-steel
                                                                     border border-gray-200 dark:border-cerberus-steel/30">
                                                            <span class="material-icons text-xs">location_on</span>
                                                            {{ $item->asignacion->empresa->nombre }}
                                                        </span>
                                                    @endif

                                                    {{-- Fecha de asignación --}}
                                                    <p class="text-xs text-gray-300 dark:text-cerberus-steel/50 mt-1">
                                                        Asignado: {{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Periféricos --}}
                                        @if ($item->hijos->where('devuelto', false)->isNotEmpty())
                                            <div class="border-t border-gray-100 dark:border-cerberus-steel/30
                                                        px-4 py-2 bg-gray-50 dark:bg-cerberus-mid/50 space-y-1">
                                                @foreach ($item->hijos->where('devuelto', false) as $hijo)
                                                    <p class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1.5">
                                                        <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                                        <span class="font-medium text-gray-700 dark:text-cerberus-light">
                                                            {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                                        </span>
                                                        <span class="text-gray-400 dark:text-cerberus-steel/60">
                                                            {{ $hijo->equipo?->categoria?->nombre ?? '—' }}
                                                            @if ($hijo->equipo?->serial) · {{ $hijo->equipo->serial }} @endif
                                                        </span>
                                                        @if ($hijo->equipo?->ubicacion)
                                                            <span class="ml-auto text-gray-300 dark:text-cerberus-steel/40 flex items-center gap-0.5">
                                                                <span class="material-icons text-xs">location_on</span>
                                                                {{ $hijo->equipo->ubicacion->nombre }}
                                                            </span>
                                                        @endif
                                                    </p>
                                                @endforeach
                                            </div>
                                        @endif

                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

                {{-- ── Footer ──────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-6 py-4
                            border-t border-gray-100 dark:border-cerberus-steel/50 flex-shrink-0">
                    <a href="{{ route('admin.asignaciones.historial', $this->usuario) }}"
                       wire:navigate
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium
                              text-cerberus-accent hover:text-white
                              border border-cerberus-accent/40 hover:border-cerberus-accent
                              hover:bg-cerberus-accent/10 transition-all duration-150">
                        <span class="material-icons text-base">history</span>
                        Ver historial completo
                    </a>
                    <a href="{{ route('admin.asignaciones.devolver.usuario', $this->usuario) }}"
                       wire:navigate
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium
                              text-white bg-cerberus-primary hover:bg-cerberus-primary/80 transition">
                        <span class="material-icons text-base">assignment_return</span>
                        Registrar devolución
                    </a>
                </div>

            </div>
        </div>
    @endif
</div>