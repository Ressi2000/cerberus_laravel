{{--
    livewire/asignaciones/devolver-usuario.blade.php — v2
    ─────────────────────────────────────────────────────────────────────────
    Devolución unificada por usuario.
    Muestra TODOS los items activos (principales Y periféricos) con checkbox
    individual. Los items se agrupan por asignación para dar contexto.
    Cada periférico aparece indentado bajo su equipo principal.

    Cambios respecto a v1:
    · Incluye periféricos con checkbox propio (antes solo mostraba principales)
    · Banner ámbar si hay periféricos que quedarán huérfanos (Opción A)
    · Agrupación por asignación (útil cuando el usuario tiene varias)
    · Indicador "Periférico de CODIGO" en cada item hijo
    ─────────────────────────────────────────────────────────────────────────
--}}
<div class="space-y-6">

    {{-- ── Error general ───────────────────────────────────────────────────── --}}
    @error('general')
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg
                    bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50
                    text-red-700 dark:text-red-300 text-sm">
            <span class="material-icons text-base flex-shrink-0">error_outline</span>
            {{ $message }}
        </div>
    @enderror

    {{-- ── Card del usuario ───────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-5">
        <div class="flex items-center gap-3">
            <img src="{{ $this->usuario->foto_url }}"
                 alt="{{ $this->usuario->name }}"
                 class="w-10 h-10 rounded-full object-cover
                        border border-gray-200 dark:border-cerberus-steel/50 flex-shrink-0">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $this->usuario->name }}
                </p>
                <p class="text-xs text-gray-400 dark:text-cerberus-steel truncate">
                    {{ $this->usuario->empresaNomina?->nombre ?? '—' }}
                    · {{ $this->usuario->cargo?->nombre ?? '—' }}
                    · Ficha: {{ $this->usuario->ficha ?? '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── Aviso de huérfanos ───────────────────────────────────────────────── --}}
    @if ($this->principalesConHuerfanos->isNotEmpty())
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl
                    bg-amber-50 dark:bg-amber-900/20
                    border border-amber-300 dark:border-amber-600/50">
            <span class="material-icons text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5">info</span>
            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-1">
                    Periféricos que quedarán libres
                </p>
                <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                    Estás devolviendo equipos principales que tienen periféricos asociados
                    <strong>no seleccionados</strong>. Al confirmar, esos periféricos
                    se convertirán en equipos principales y seguirán asignados al usuario.
                    Selecciónalos ahora si también quieres devolverlos.
                </p>
                <ul class="mt-2 space-y-0.5">
                    @foreach ($this->principalesConHuerfanos as $principal)
                        @foreach ($principal->hijosActivos as $hijo)
                            @if (! in_array((string) $hijo->id, $seleccionados))
                                <li class="text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                    <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                    <strong>{{ $hijo->equipo?->codigo_interno ?? '—' }}</strong>
                                    · {{ $hijo->equipo?->categoria?->nombre ?? '—' }}
                                    @if ($hijo->equipo?->serial)
                                        · S/N {{ $hijo->equipo->serial }}
                                    @endif
                                    <span class="text-amber-500/70">
                                        (periférico de {{ $principal->equipo?->codigo_interno ?? '—' }})
                                    </span>
                                </li>
                            @endif
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ── Panel de selección ──────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl overflow-hidden">

        {{-- Cabecera: seleccionar todos + contador --}}
        <div class="flex items-center justify-between px-5 py-3.5
                    border-b border-gray-100 dark:border-cerberus-steel/50">
            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox"
                       wire:model.live="seleccionarTodos"
                       class="w-4 h-4 rounded border-gray-300 dark:border-cerberus-steel
                              text-cerberus-primary bg-white dark:bg-cerberus-dark
                              focus:ring-cerberus-primary/30 transition" />
                <span class="text-sm font-medium text-gray-700 dark:text-cerberus-light">
                    Seleccionar todos
                </span>
            </label>
            <span class="text-xs text-gray-400 dark:text-cerberus-steel">
                {{ count($seleccionados) }} de {{ $this->todosLosItemsActivos->count() }} seleccionado(s)
                <span class="text-gray-300 dark:text-cerberus-steel/40 mx-1">·</span>
                {{ $this->todosLosItemsActivos->whereNull('equipo_padre_id')->count() }} principal(es),
                {{ $this->todosLosItemsActivos->whereNotNull('equipo_padre_id')->count() }} periférico(s)
            </span>
        </div>

        @error('seleccionados')
            <div class="px-5 py-2 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-700/30">
                <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                    <span class="material-icons text-xs">error_outline</span> {{ $message }}
                </p>
            </div>
        @enderror

        {{-- Items agrupados por asignación --}}
        @php
            // Agrupar todos los items activos por asignación para dar contexto visual
            $itemsPorAsignacion = $this->todosLosItemsActivos->groupBy('asignacion_id');
        @endphp

        @forelse ($itemsPorAsignacion as $asignacionId => $items)
            @php
                $primeraAsignacion = $items->first()->asignacion;
            @endphp

            {{-- Separador de asignación (solo si hay más de una) --}}
            @if ($itemsPorAsignacion->count() >= 1)
                <div class="px-5 py-2 bg-gray-50 dark:bg-cerberus-dark/60
                            border-b border-gray-100 dark:border-cerberus-steel/30
                            flex items-center gap-2">
                    <span class="material-icons text-xs text-gray-400 dark:text-cerberus-steel">assignment</span>
                    <span class="text-xs text-gray-500 dark:text-cerberus-accent font-medium">
                        Asignación #{{ $asignacionId }}
                        · {{ $primeraAsignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                        · {{ $primeraAsignacion?->empresa?->nombre ?? '—' }}
                    </span>
                </div>
            @endif

            {{-- Items de esta asignación --}}
            <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/30">
                @foreach ($items as $item)
                    @php
                        $esPeriférico     = $item->equipo_padre_id !== null;
                        $estaSeleccionado = in_array((string) $item->id, $seleccionados);
                        $tieneHuerfanos   = ! $esPeriférico &&
                                            $estaSeleccionado &&
                                            $item->hijosActivos->isNotEmpty() &&
                                            $item->hijosActivos->contains(
                                                fn ($h) => ! in_array((string) $h->id, $seleccionados)
                                            );
                    @endphp

                    <div wire:key="item-{{ $item->id }}"
                         x-data="{ showObs: false }"
                         class="transition-colors duration-150
                                {{ $estaSeleccionado
                                    ? 'bg-cerberus-primary/5 dark:bg-cerberus-primary/10'
                                    : '' }}
                                {{ $esPeriférico
                                    ? 'pl-10 border-l-2 border-cerberus-primary/20 dark:border-cerberus-primary/30'
                                    : '' }}">

                        <div class="flex items-start gap-4 px-5 py-4">

                            {{-- Checkbox --}}
                            <div class="pt-0.5 flex-shrink-0">
                                <input type="checkbox"
                                       value="{{ $item->id }}"
                                       wire:model.live="seleccionados"
                                       class="w-4 h-4 rounded border-gray-300 dark:border-cerberus-steel
                                              text-cerberus-primary bg-white dark:bg-cerberus-dark
                                              focus:ring-cerberus-primary/30 transition" />
                            </div>

                            {{-- Datos del equipo --}}
                            <div class="flex-1 min-w-0">

                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">

                                        {{-- Indicador de periférico --}}
                                        @if ($esPeriférico)
                                            <p class="text-xs text-cerberus-primary/60 dark:text-cerberus-accent/60
                                                       flex items-center gap-1 mb-0.5">
                                                <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                                Periférico de
                                                <strong>{{ $item->padre?->equipo?->codigo_interno ?? '—' }}</strong>
                                            </p>
                                        @endif

                                        {{-- Nombre y categoría --}}
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $item->equipo?->codigo_interno ?? '—' }}
                                            <span class="font-normal text-gray-500 dark:text-cerberus-steel">
                                                · {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                            </span>
                                        </p>

                                        <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                            {{ $item->equipo?->nombre_maquina ?? '—' }}
                                            @if ($item->equipo?->serial)
                                                · S/N: {{ $item->equipo->serial }}
                                            @endif
                                        </p>

                                        {{-- Aviso inline de huérfanos --}}
                                        @if ($tieneHuerfanos)
                                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1
                                                       flex items-center gap-1">
                                                <span class="material-icons text-xs">warning_amber</span>
                                                Sus periféricos no seleccionados se promoverán a principales
                                            </p>
                                        @endif

                                        {{-- Lista de periféricos activos (referencia visual) --}}
                                        @if (! $esPeriférico && $item->hijosActivos->isNotEmpty())
                                            <div class="mt-1.5 space-y-0.5">
                                                @foreach ($item->hijosActivos as $hijo)
                                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel/70
                                                               flex items-center gap-1">
                                                        <span class="material-icons text-xs">subdirectory_arrow_right</span>
                                                        {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                                        ({{ $hijo->equipo?->categoria?->nombre ?? '—' }})
                                                        @if (in_array((string) $hijo->id, $seleccionados))
                                                            <span class="text-emerald-500 dark:text-emerald-400
                                                                          text-xs flex items-center gap-0.5">
                                                                <span class="material-icons text-xs">check</span>
                                                                seleccionado
                                                            </span>
                                                        @endif
                                                    </p>
                                                @endforeach
                                            </div>
                                        @endif

                                    </div>

                                    {{-- Toggle nota --}}
                                    <button type="button"
                                            @click="showObs = !showObs"
                                            class="flex-shrink-0 text-xs text-cerberus-primary dark:text-cerberus-accent
                                                   hover:underline transition">
                                        <span x-text="showObs ? 'Ocultar nota' : '+ Nota'"></span>
                                    </button>
                                </div>

                                {{-- Textarea de observación --}}
                                <div x-show="showObs" x-collapse class="mt-3">
                                    <textarea wire:model="observaciones.{{ $item->id }}"
                                              placeholder="Estado físico, motivo, observaciones..."
                                              rows="2"
                                              class="w-full text-sm rounded-lg px-3 py-2
                                                     bg-gray-50 dark:bg-cerberus-dark
                                                     border border-gray-200 dark:border-cerberus-steel/60
                                                     text-gray-800 dark:text-white
                                                     placeholder-gray-400 dark:placeholder-cerberus-steel
                                                     focus:border-cerberus-primary focus:ring-1 focus:ring-cerberus-primary/30
                                                     resize-none transition">
                                    </textarea>
                                    @error("observaciones.{$item->id}")
                                        <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                @endforeach
            </div>

        @empty
            <div class="px-5 py-12 text-center">
                <span class="material-icons text-4xl text-gray-200 dark:text-cerberus-steel/30 block mb-2">
                    inventory
                </span>
                <p class="text-sm text-gray-400 dark:text-cerberus-steel">
                    Este usuario no tiene equipos activos.
                </p>
            </div>
        @endforelse

    </div>

    {{-- ── Barra de acción ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4
                bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl px-5 py-4">

        <div class="text-sm text-gray-500 dark:text-cerberus-light">
            @if (count($seleccionados) > 0)
                <span class="font-semibold text-gray-900 dark:text-white">
                    {{ count($seleccionados) }}
                </span>
                equipo(s) pasarán a
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs
                             bg-emerald-100 dark:bg-emerald-900/30
                             text-emerald-700 dark:text-emerald-400 font-medium">
                    <span class="material-icons text-xs">check_circle</span>
                    Disponible
                </span>
            @else
                <span class="text-gray-400 dark:text-cerberus-steel">Ningún equipo seleccionado</span>
            @endif
        </div>

        <div class="flex items-center gap-3">

            <a href="{{ route('admin.asignaciones.historial', $this->usuarioId) }}"
               wire:navigate
               class="px-4 py-2 rounded-lg text-sm bg-gray-100 dark:bg-cerberus-dark
                      text-gray-700 dark:text-cerberus-light border border-gray-200 dark:border-cerberus-steel/40
                      hover:bg-gray-200 dark:hover:bg-cerberus-steel/40 transition">
                Cancelar
            </a>

            <button type="button"
                    wire:click="confirmar"
                    wire:loading.attr="disabled"
                    @disabled(empty($seleccionados))
                    class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium
                           bg-cerberus-primary hover:bg-cerberus-hover text-white
                           transition disabled:opacity-40 disabled:cursor-not-allowed">

                <span wire:loading.remove wire:target="confirmar">
                    <span class="material-icons text-base">keyboard_return</span>
                </span>
                <span wire:loading wire:target="confirmar">
                    <span class="material-icons text-base animate-spin">refresh</span>
                </span>
                <span wire:loading.remove wire:target="confirmar">
                    Confirmar devolución
                    @if (count($seleccionados) > 0)({{ count($seleccionados) }})@endif
                </span>
                <span wire:loading wire:target="confirmar">Procesando...</span>

            </button>
        </div>
    </div>

</div>