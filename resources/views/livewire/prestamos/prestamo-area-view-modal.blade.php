{{--
    prestamo-area-view-modal.blade.php
    Modal de detalle para préstamos a áreas comunes.
    Escucha el evento openPrestamoAreaView { id } desde la tabla de préstamos.
--}}
<div>
    @if ($open && $this->prestamo)

        {{-- ── Overlay ──────────────────────────────────────────────────────── --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data
             x-on:keydown.escape.window="$wire.cerrar()">

            {{-- Fondo --}}
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                 wire:click="cerrar"></div>

            {{-- Panel --}}
            <div class="relative z-10 w-full max-w-2xl max-h-[90vh] flex flex-col
                        bg-white dark:bg-cerberus-mid
                        rounded-2xl shadow-2xl
                        border border-gray-200 dark:border-cerberus-steel/40
                        overflow-hidden">

                {{-- ── Header ───────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-6 py-4
                            border-b border-gray-200 dark:border-cerberus-steel/50
                            bg-gray-50 dark:bg-cerberus-dark flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="p-2 rounded-xl bg-[#1E40AF]/10 dark:bg-[#1E40AF]/20">
                            <span class="material-icons text-[#1E40AF] dark:text-cerberus-accent">corporate_fare</span>
                        </span>
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                {{ $this->prestamo->areaDepartamento?->nombre ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1.5">
                                {{ $this->prestamo->areaEmpresa?->nombre ?? '—' }}
                                · Área común · Préstamo #{{ $this->prestamo->id }}
                                <x-prestamos.badge-estado :estado="$this->prestamo->estado" />
                            </p>
                        </div>
                    </div>
                    <button wire:click="cerrar"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-gray-600 dark:hover:text-white
                                   hover:bg-gray-200 dark:hover:bg-cerberus-steel/40 transition flex-shrink-0 ml-3">
                        <span class="material-icons text-xl">close</span>
                    </button>
                </div>

                {{-- ── Cuerpo (scroll) ───────────────────────────────────────── --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    {{-- Datos del área --}}
                    <div class="grid grid-cols-2 gap-3">

                        <div class="col-span-2 sm:col-span-1 rounded-xl px-4 py-3
                                    bg-gray-50 dark:bg-cerberus-dark
                                    border border-gray-200 dark:border-cerberus-steel/40">
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel mb-0.5">Empresa del área</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->prestamo->areaEmpresa?->nombre ?? '—' }}
                            </p>
                        </div>

                        <div class="col-span-2 sm:col-span-1 rounded-xl px-4 py-3
                                    bg-gray-50 dark:bg-cerberus-dark
                                    border border-gray-200 dark:border-cerberus-steel/40">
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel mb-0.5">Departamento</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->prestamo->areaDepartamento?->nombre ?? '—' }}
                            </p>
                        </div>

                        <div class="col-span-2 rounded-xl px-4 py-3
                                    bg-gray-50 dark:bg-cerberus-dark
                                    border border-gray-200 dark:border-cerberus-steel/40">
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel mb-0.5">Responsable del área</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->prestamo->areaResponsable?->name ?? '—' }}
                            </p>
                            @if ($this->prestamo->areaResponsable?->cargo)
                                <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-0.5">
                                    {{ $this->prestamo->areaResponsable->cargo->nombre }}
                                </p>
                            @endif
                        </div>

                        <div class="rounded-xl px-4 py-3
                                    bg-gray-50 dark:bg-cerberus-dark
                                    border border-gray-200 dark:border-cerberus-steel/40">
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel mb-0.5">Fecha de préstamo</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>

                        <div class="rounded-xl px-4 py-3
                                    bg-gray-50 dark:bg-cerberus-dark
                                    border border-gray-200 dark:border-cerberus-steel/40">
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel mb-0.5">Devolución esperada</p>
                            <p class="text-sm font-medium {{ $this->prestamo->estaVencido() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $this->prestamo->fecha_devolucion_esperada?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>

                        <div class="col-span-2 rounded-xl px-4 py-3
                                    bg-gray-50 dark:bg-cerberus-dark
                                    border border-gray-200 dark:border-cerberus-steel/40">
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel mb-0.5">Analista</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->prestamo->analista?->name ?? '—' }}
                            </p>
                        </div>

                    </div>

                    {{-- Equipos activos --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider
                                   text-gray-500 dark:text-cerberus-steel mb-2 flex items-center gap-2">
                            <span class="material-icons text-base text-emerald-500">devices</span>
                            Equipos activos
                            <span class="px-1.5 py-0.5 rounded-full text-xs
                                         bg-emerald-100 dark:bg-emerald-900/30
                                         text-emerald-700 dark:text-emerald-400">
                                {{ $this->itemsActivos->count() }}
                            </span>
                        </h3>

                        @forelse ($this->itemsActivos as $item)
                            <div class="mb-2 rounded-xl border border-gray-200 dark:border-cerberus-steel/40
                                        bg-white dark:bg-cerberus-dark overflow-hidden">

                                {{-- Equipo principal --}}
                                <div class="flex items-start justify-between gap-3 px-4 py-3">
                                    <div class="flex items-start gap-2 min-w-0">
                                        <span class="material-icons text-base text-[#1E40AF]/70 dark:text-cerberus-accent mt-0.5 flex-shrink-0">
                                            {{ match(strtolower($item->equipo?->categoria?->nombre ?? '')) {
                                                'laptop', 'portátil', 'notebook' => 'laptop',
                                                'desktop', 'pc', 'computadora' => 'desktop_windows',
                                                'monitor', 'pantalla' => 'monitor',
                                                'impresora', 'printer' => 'print',
                                                'teléfono', 'telefono', 'celular' => 'smartphone',
                                                'switch', 'router', 'red' => 'router',
                                                'servidor', 'server' => 'dns',
                                                default => 'devices'
                                            } }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $item->equipo?->codigo_interno ?? '—' }}
                                                <span class="font-normal text-gray-500 dark:text-cerberus-steel">
                                                    · {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                                </span>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-0.5">
                                                {{ $item->equipo?->ubicacion?->nombre ?? '—' }}
                                            </p>
                                            {{-- Atributos EAV relevantes --}}
                                            @php
                                                $attrs = $item->equipo?->atributosActuales
                                                    ->filter(fn($v) => $v->atributo?->visible_en_tabla)
                                                    ->take(3);
                                            @endphp
                                            @if($attrs && $attrs->isNotEmpty())
                                                <p class="text-xs text-gray-500 dark:text-cerberus-steel/70 mt-0.5">
                                                    {{ $attrs->map(fn($v) => $v->atributo->nombre . ': ' . $v->valor)->implode(' · ') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Periféricos --}}
                                @if ($item->hijos->where('devuelto', false)->isNotEmpty())
                                    <div class="border-t border-gray-100 dark:border-cerberus-steel/30
                                                px-4 py-2 space-y-1 bg-gray-50 dark:bg-cerberus-mid/50">
                                        @foreach ($item->hijos->where('devuelto', false) as $hijo)
                                            <p class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1.5">
                                                <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                                {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                                <span class="text-gray-500 dark:text-cerberus-steel/60">
                                                    {{ $hijo->equipo?->categoria?->nombre ?? '—' }}
                                                </span>
                                            </p>
                                        @endforeach
                                    </div>
                                @endif

                            </div>
                        @empty
                            <div class="flex items-center gap-3 px-4 py-4 rounded-xl
                                        bg-gray-50 dark:bg-cerberus-dark/50
                                        border border-dashed border-gray-200 dark:border-cerberus-steel/40
                                        text-gray-500 dark:text-cerberus-steel text-sm">
                                <span class="material-icons text-xl opacity-40">inbox</span>
                                No hay equipos activos en esta área.
                            </div>
                        @endforelse
                    </div>

                    {{-- Equipos devueltos --}}
                    @if ($this->itemsDevueltos->isNotEmpty())
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider
                                       text-gray-500 dark:text-cerberus-steel mb-2 flex items-center gap-2">
                                <span class="material-icons text-base text-gray-400">assignment_return</span>
                                Equipos devueltos
                            </h3>
                            <div class="space-y-1.5">
                                @foreach ($this->itemsDevueltos as $item)
                                    <div class="flex items-center justify-between gap-3 px-4 py-2 rounded-lg
                                                bg-gray-50 dark:bg-cerberus-dark/50
                                                border border-gray-200 dark:border-cerberus-steel/30">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $item->equipo?->codigo_interno ?? '—' }}
                                            <span class="font-normal text-gray-500 dark:text-cerberus-steel">
                                                · {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                            </span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-cerberus-steel flex-shrink-0">
                                            Dev. {{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}
                                            @if ($item->devueltoPor)
                                                · {{ $item->devueltoPor->name }}
                                            @endif
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Observaciones --}}
                    @if ($this->prestamo->observaciones)
                        <div class="rounded-xl px-4 py-3
                                    bg-amber-50 dark:bg-amber-900/10
                                    border border-amber-200 dark:border-amber-700/40">
                            <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1">Observaciones</p>
                            <p class="text-sm text-amber-800 dark:text-amber-300">
                                {{ $this->prestamo->observaciones }}
                            </p>
                        </div>
                    @endif

                </div>

                {{-- ── Pie ───────────────────────────────────────────────────── --}}
                <div class="sticky bottom-0 flex items-center justify-between gap-3 px-6 py-4
                            bg-white dark:bg-cerberus-mid
                            border-t border-gray-200 dark:border-cerberus-steel/50
                            flex-shrink-0">

                    <div class="flex items-center gap-2">
                        {{-- Planilla de préstamo --}}
                        <a href="{{ route('admin.prestamos.planilla.prestamo', $this->prestamo) }}"
                           target="_blank"
                           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                  bg-gray-100 dark:bg-cerberus-dark
                                  text-gray-700 dark:text-cerberus-light
                                  border border-gray-200 dark:border-cerberus-steel/40
                                  hover:border-cerberus-primary dark:hover:border-cerberus-accent transition">
                            <span class="material-icons text-sm">download</span>
                            Planilla
                        </a>

                        {{-- Renovar préstamo --}}
                        @if ($this->prestamo->estaActivo())
                            <button type="button"
                                    wire:click="$dispatch('abrir-renovar', { prestamoId: {{ $this->prestamo->id }} })"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                           bg-blue-50 dark:bg-blue-900/20
                                           text-blue-700 dark:text-blue-400
                                           border border-blue-200 dark:border-blue-700/40
                                           hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                                <span class="material-icons text-sm">event</span>
                                Renovar
                            </button>
                        @endif

                        {{-- Registrar devolución --}}
                        @if ($this->itemsActivos->isNotEmpty())
                            <a href="{{ route('admin.prestamos.devolver', $this->prestamo) }}"
                               wire:navigate
                               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                      bg-amber-50 dark:bg-amber-900/20
                                      text-amber-700 dark:text-amber-400
                                      border border-amber-200 dark:border-amber-700/40
                                      hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                                <span class="material-icons text-sm">keyboard_return</span>
                                Registrar devolución
                            </a>
                        @endif
                    </div>

                    <button wire:click="cerrar"
                            class="px-4 py-2 rounded-lg text-sm
                                   bg-gray-100 dark:bg-cerberus-dark
                                   text-gray-700 dark:text-cerberus-light
                                   border border-gray-200 dark:border-cerberus-steel/40
                                   hover:bg-gray-200 dark:hover:bg-cerberus-steel/40 transition">
                        Cerrar
                    </button>

                </div>

            </div>
        </div>

    @endif
</div>
