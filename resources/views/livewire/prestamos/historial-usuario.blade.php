{{--
    historial-usuario.blade.php (Préstamos)
    ─────────────────────────────────────────────────────────────────────────
    Análogo a livewire/asignaciones/historial-usuario.blade.php, adaptado a
    los 3 estados de Prestamo (Activo | Vencido | Cerrado).
    ─────────────────────────────────────────────────────────────────────────
--}}
<div class="space-y-6">

    @livewire('prestamos.renovar-prestamo')

    {{-- ══ TARJETA DE PERFIL ══════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-4">
                <img src="{{ $this->usuario->foto_url }}" alt="{{ $this->usuario->name }}"
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
                        <span class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1">
                            <span class="material-icons text-xs">business</span>
                            {{ $this->usuario->empresaNomina?->nombre ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1">
                            <span class="material-icons text-xs">location_on</span>
                            {{ $this->usuario->ubicacion?->nombre ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1">
                            <span class="material-icons text-xs">supervisor_account</span>
                            {{ $this->usuario->jefe?->name ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ STATS DEL USUARIO ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl px-5 py-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ $this->statsUsuario['equipos_activos'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-1">Equipos activos</p>
        </div>
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl px-5 py-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                {{ $this->statsUsuario['vencidos'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-1">Préstamos vencidos</p>
        </div>
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl px-5 py-4 text-center">
            <p class="text-2xl font-bold text-cerberus-primary dark:text-cerberus-accent">
                {{ $this->statsUsuario['total_prestamos'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-1">Préstamos históricos</p>
        </div>
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl px-5 py-4 text-center">
            <p class="text-base font-bold text-gray-700 dark:text-cerberus-light">
                {{ $this->statsUsuario['ultimo_prestamo'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-1">Último préstamo</p>
        </div>
    </div>

    {{-- ══ EQUIPOS ACTIVOS ════════════════════════════════════════════════ --}}
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel mb-3 flex items-center gap-2">
            <span class="material-icons text-base text-emerald-500">devices</span>
            Equipos prestados actualmente
            <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                {{ $this->equiposActivos->count() }}
            </span>
        </h3>

        @if ($this->equiposActivos->isEmpty())
            <div class="flex items-center gap-3 px-5 py-4 rounded-xl bg-gray-50 dark:bg-cerberus-dark/50
                        border border-dashed border-gray-200 dark:border-cerberus-steel/40
                        text-gray-500 dark:text-cerberus-steel text-sm">
                <span class="material-icons text-2xl opacity-40">inbox</span>
                Sin equipos en préstamo en este momento.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->equiposActivos as $item)
                    @php
                        $atributos = $item->equipo?->atributosActuales
                            ->filter(fn($v) => $v->atributo?->visible_en_tabla)
                            ->sortBy(fn($v) => $v->atributo?->orden ?? 99)
                            ->take(4);
                    @endphp
                    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl overflow-hidden">
                        <div class="flex items-start justify-between gap-3 px-5 py-3">
                            <div class="flex items-start gap-2 min-w-0">
                                <span class="material-icons text-base text-cerberus-accent mt-0.5 flex-shrink-0">devices</span>
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
                                    @if ($atributos && $atributos->isNotEmpty())
                                        <p class="text-xs text-gray-500 dark:text-cerberus-steel/70 mt-0.5">
                                            {{ $atributos->map(fn($v) => $v->atributo->nombre . ': ' . $v->valor)->implode(' · ') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            @if ($item->prestamo?->empresa)
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                                    {{ $item->prestamo->empresa->nombre }}
                                </p>
                            @endif
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                             {{ $item->prestamo?->estaVencido()
                                                 ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                                                 : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' }}">
                                    {{ $item->prestamo?->fecha_prestamo?->format('d/m/Y') ?? '—' }}
                                </span>
                                @if ($item->prestamo?->fecha_devolucion_esperada)
                                    <span class="text-xs {{ $item->prestamo?->estaVencido() ? 'text-red-500 font-semibold' : 'text-gray-400 dark:text-cerberus-steel' }}">
                                        Devolver: {{ $item->prestamo->fecha_devolucion_esperada->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($item->hijos->where('devuelto', false)->isNotEmpty())
                            <div class="border-t border-gray-100 dark:border-cerberus-steel/30 px-5 py-2 space-y-1 bg-gray-50 dark:bg-cerberus-dark/50">
                                @foreach ($item->hijos->where('devuelto', false) as $hijo)
                                    <p class="text-xs text-gray-500 dark:text-cerberus-steel flex items-center gap-1.5">
                                        <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                        {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                        · {{ $hijo->equipo?->categoria?->nombre ?? '—' }}
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ══ TIMELINE DE PRÉSTAMOS ══════════════════════════════════════════ --}}
    <section>
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel flex items-center gap-2">
                <span class="material-icons text-base text-purple-500">history</span>
                Historial de préstamos
            </h3>

            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex rounded-lg border border-gray-200 dark:border-cerberus-steel/50 overflow-hidden text-xs">
                    @foreach (['todos' => 'Todos', 'activo' => 'Activos', 'vencido' => 'Vencidos', 'cerrado' => 'Cerrados'] as $val => $label)
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

                @if (count($this->aniosDisponibles) > 1)
                    <select wire:model.live="filtroAnio"
                        class="text-xs rounded-lg px-2.5 py-1.5 bg-white dark:bg-cerberus-dark
                               border border-gray-200 dark:border-cerberus-steel/50
                               text-gray-700 dark:text-cerberus-light focus:border-cerberus-primary transition">
                        <option value="">Todos los años</option>
                        @foreach ($this->aniosDisponibles as $anio)
                            <option value="{{ $anio }}">{{ $anio }}</option>
                        @endforeach
                    </select>
                @endif

                @if ($filtroEstado !== 'todos' || $filtroAnio)
                    <button wire:click="resetFiltros"
                        class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                        <span class="material-icons text-xs">close</span>
                        Limpiar
                    </button>
                @endif
            </div>
        </div>

        @if ($this->prestamos->isEmpty())
            <div class="flex items-center gap-3 px-5 py-4 rounded-xl bg-gray-50 dark:bg-cerberus-dark/50
                        border border-dashed border-gray-200 dark:border-cerberus-steel/40
                        text-gray-500 dark:text-cerberus-steel text-sm">
                <span class="material-icons text-2xl opacity-40">history</span>
                Sin préstamos para los filtros seleccionados.
            </div>
        @else
            <div class="relative">
                <div class="absolute left-5 top-0 bottom-0 w-px bg-gray-200 dark:bg-cerberus-steel/30"></div>

                <div class="space-y-4">
                    @foreach ($this->prestamos as $prestamo)
                        <div wire:key="hist-{{ $prestamo->id }}" x-data="{ open: {{ $prestamo->estaActivo() ? 'true' : 'false' }} }" class="relative flex gap-4">

                            <div class="relative z-10 flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full
                                        {{ $prestamo->estado === 'Vencido'
                                            ? 'bg-red-100 dark:bg-red-900/30 border-2 border-red-400 dark:border-red-600'
                                            : ($prestamo->estado === 'Activo'
                                                ? 'bg-emerald-100 dark:bg-emerald-900/30 border-2 border-emerald-400 dark:border-emerald-600'
                                                : 'bg-gray-100 dark:bg-cerberus-steel/20 border-2 border-gray-300 dark:border-cerberus-steel/50') }}">
                                <span class="material-icons text-base
                                             {{ $prestamo->estado === 'Vencido'
                                                 ? 'text-red-600 dark:text-red-400'
                                                 : ($prestamo->estado === 'Activo'
                                                     ? 'text-emerald-600 dark:text-emerald-400'
                                                     : 'text-gray-500 dark:text-cerberus-steel') }}">
                                    {{ $prestamo->estado === 'Vencido' ? 'schedule' : ($prestamo->estado === 'Activo' ? 'swap_horiz' : 'lock') }}
                                </span>
                            </div>

                            <div class="flex-1 min-w-0 bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl overflow-hidden mb-1">

                                <div role="button" tabindex="0"
                                    @click="open = !open" @keydown.enter="open = !open"
                                    class="w-full flex items-center justify-between gap-3 px-5 py-3 cursor-pointer
                                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition text-left">

                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-prestamos.badge-estado :estado="$prestamo->estado" />
                                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-cerberus-steel hidden sm:block">
                                            {{ $prestamo->empresa?->nombre ?? '—' }}
                                            · Analista: {{ $prestamo->analista?->name ?? '—' }}
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-cerberus-steel/50">
                                            {{ $prestamo->items->count() }} equipo(s)
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if ($prestamo->estaActivo())
                                            <button type="button"
                                                wire:click="$dispatch('abrir-renovar', { prestamoId: {{ $prestamo->id }} })"
                                                @click.stop title="Renovar préstamo"
                                                class="p-1 rounded text-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                                <span class="material-icons text-sm">event</span>
                                            </button>
                                        @endif

                                        <a href="{{ route('admin.prestamos.planilla.prestamo', $prestamo) }}"
                                            target="_blank" @click.stop title="Planilla de préstamo"
                                            class="p-1 rounded text-gray-500 hover:text-cerberus-primary dark:hover:text-cerberus-accent transition">
                                            <span class="material-icons text-sm">download</span>
                                        </a>

                                        @if ($prestamo->itemsDevueltos->count() > 0)
                                            <a href="{{ route('admin.prestamos.planilla.devolucion', $prestamo) }}"
                                                target="_blank" @click.stop title="Planilla de devolución"
                                                class="p-1 rounded text-amber-400 hover:text-amber-500 transition">
                                                <span class="material-icons text-sm">keyboard_return</span>
                                            </a>
                                        @endif

                                        <span class="material-icons text-base text-gray-500 dark:text-cerberus-steel transition-transform duration-200"
                                            :class="open ? 'rotate-180' : ''">
                                            expand_more
                                        </span>
                                    </div>
                                </div>

                                <div x-show="open" x-collapse class="border-t border-gray-100 dark:border-cerberus-steel/30 px-5 py-3 space-y-2">

                                    @foreach ($prestamo->items as $item)
                                        @php $esPeriférico = $item->equipo_padre_id !== null; @endphp
                                        <div class="flex items-start gap-2 {{ $esPeriférico ? 'ml-5 mt-0' : '' }}">

                                            <span class="material-icons text-base mt-0.5 flex-shrink-0
                                                         {{ $item->devuelto
                                                             ? 'text-gray-400 dark:text-cerberus-steel/40'
                                                             : ($esPeriférico
                                                                 ? 'text-cerberus-primary/40 dark:text-cerberus-accent/50'
                                                                 : 'text-emerald-500 dark:text-emerald-400') }}">
                                                {{ $esPeriférico ? 'subdirectory_arrow_right' : ($item->devuelto ? 'check_circle' : 'devices') }}
                                            </span>

                                            <div class="min-w-0">
                                                <p class="text-sm
                                                           {{ $item->devuelto
                                                               ? 'text-gray-500 dark:text-cerberus-steel line-through'
                                                               : ($esPeriférico
                                                                   ? 'text-gray-600 dark:text-cerberus-light'
                                                                   : 'text-gray-900 dark:text-white') }}">
                                                    {{ $item->equipo?->codigo_interno ?? '—' }}
                                                    <span class="font-normal text-gray-500 dark:text-cerberus-steel text-xs">
                                                        · {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                                    </span>
                                                    @if ($esPeriférico)
                                                        <span class="text-xs text-cerberus-primary/50 dark:text-cerberus-accent/50">
                                                            (periférico)
                                                        </span>
                                                    @endif
                                                </p>
                                                @if ($item->devuelto)
                                                    <p class="text-xs text-gray-500 dark:text-cerberus-steel">
                                                        Dev. {{ $item->fecha_devolucion?->format('d/m/Y') ?? '—' }}
                                                        @if ($item->devueltoPor)
                                                            · {{ $item->devueltoPor->name }}
                                                        @endif
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

            @if ($this->prestamos->hasPages())
                <div class="mt-4 flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500 dark:text-cerberus-steel">
                        Mostrando {{ $this->prestamos->firstItem() }}–{{ $this->prestamos->lastItem() }}
                        de {{ $this->prestamos->total() }} préstamos
                    </p>
                    {{ $this->prestamos->links('vendor.livewire.cerberus-pagination') }}
                </div>
            @endif

        @endif

    </section>

</div>
