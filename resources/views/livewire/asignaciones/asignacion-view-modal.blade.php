<div>
    @if ($open && $this->usuario)

        {{-- Overlay --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4
                    bg-black/60 backdrop-blur-sm"
             wire:click.self="cerrar">

            {{-- Panel --}}
            <div class="relative w-full max-w-lg max-h-[85vh] overflow-y-auto
                        bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel
                        rounded-2xl shadow-2xl animate-fade-in">

                {{-- Cabecera --}}
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4
                            bg-white dark:bg-cerberus-mid
                            border-b border-gray-200 dark:border-cerberus-steel/50">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ $this->usuario->foto_url }}"
                             alt="{{ $this->usuario->name }}"
                             class="w-9 h-9 rounded-full object-cover flex-shrink-0
                                    border border-gray-200 dark:border-cerberus-steel/50">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $this->usuario->name }}
                            </h2>
                            <p class="text-xs text-gray-400 dark:text-cerberus-steel truncate">
                                {{ $this->usuario->empresaNomina?->nombre ?? '—' }} ·
                                {{ $this->usuario->cargo?->nombre ?? '—' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="cerrar"
                            class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-lg
                                   text-gray-400 dark:text-cerberus-steel
                                   hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
                                   hover:text-gray-700 dark:hover:text-white transition">
                        <span class="material-icons text-lg">close</span>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="p-6 space-y-5">

                    {{-- Resumen rápido del usuario --}}
                    <div class="grid grid-cols-2 gap-3">
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
                            <p class="text-xs text-gray-400 dark:text-cerberus-steel">Sede</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->usuario->ubicacion?->nombre ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 dark:text-cerberus-steel">Supervisor</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->usuario->jefe?->name ?? '—' }}
                            </p>
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
                            <span class="text-xs text-gray-400 dark:text-cerberus-steel">
                                {{ $this->equiposActivos->count() }} equipo(s)
                            </span>
                        </div>

                        @if ($this->equiposActivos->isEmpty())
                            <div class="text-center py-6">
                                <span class="material-icons text-3xl text-gray-200 dark:text-cerberus-steel/30 block mb-2">
                                    inventory_2
                                </span>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                                    Sin equipos asignados en este momento.
                                </p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($this->equiposActivos as $item)
                                    @php
                                        $atributos = $item->equipo?->atributosActuales
                                            ->filter(fn($v) => $v->atributo?->visible_en_tabla)
                                            ->take(3);
                                    @endphp
                                    <div class="bg-gray-50 dark:bg-cerberus-dark/60
                                                border border-gray-200 dark:border-cerberus-steel/40
                                                rounded-lg p-3">

                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $item->equipo?->codigo_interno ?? '—' }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-cerberus-accent">
                                                    {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                                    @if ($item->equipo?->serial)
                                                        · S/N: {{ $item->equipo->serial }}
                                                    @endif
                                                </p>
                                                @if ($atributos && $atributos->isNotEmpty())
                                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                                        {{ $atributos->map(fn($v) => $v->atributo->nombre . ': ' . $v->valor)->implode(' · ') }}
                                                    </p>
                                                @endif
                                            </div>
                                            <span class="flex-shrink-0 text-xs px-1.5 py-0.5 rounded
                                                         bg-emerald-100 dark:bg-emerald-900/30
                                                         text-emerald-700 dark:text-emerald-400">
                                                {{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                            </span>
                                        </div>

                                        {{-- Periféricos --}}
                                        @if ($item->hijos->where('devuelto', false)->isNotEmpty())
                                            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-cerberus-steel/30 space-y-0.5">
                                                @foreach ($item->hijos->where('devuelto', false) as $hijo)
                                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel flex items-center gap-1">
                                                        <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                                        {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                                        ({{ $hijo->equipo?->categoria?->nombre ?? '—' }})
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

                {{-- Pie --}}
                <div class="sticky bottom-0 flex items-center justify-between gap-3 px-6 py-4
                            bg-white dark:bg-cerberus-mid
                            border-t border-gray-200 dark:border-cerberus-steel/50">
                    <a href="{{ route('admin.asignaciones.historial', $this->userId) }}"
                       wire:navigate
                       class="flex items-center gap-1.5 text-xs font-medium
                              text-cerberus-primary dark:text-cerberus-accent
                              hover:text-cerberus-hover transition">
                        <span class="material-icons text-sm">history</span>
                        Ver historial completo
                    </a>
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