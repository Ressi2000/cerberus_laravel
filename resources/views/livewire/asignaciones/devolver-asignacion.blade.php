{{--
    devolver-asignacion.blade.php — v3
    ─────────────────────────────────────────────────────────────────────────
    Muestra TODOS los items activos (principales y periféricos) con
    checkbox individual. Cada periférico aparece indentado debajo de
    su equipo padre.

    Aviso de huérfanos: si el analista selecciona un principal cuyo(s)
    periférico(s) no están seleccionados, aparece un banner explicando
    que esos periféricos serán promovidos a principales automáticamente.
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

    {{-- ── Cabecera de la asignación ──────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6">

        <h2 class="text-sm font-semibold uppercase tracking-wider
                   text-gray-400 dark:text-cerberus-steel mb-4">
            Asignación a devolver
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            {{-- Receptor --}}
            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Receptor</p>
                @if ($this->asignacion->usuario_id)
                    <div class="flex items-center gap-1.5">
                        <span class="material-icons text-sm text-cerberus-accent">person</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $this->asignacion->usuario?->name ?? '—' }}
                        </span>
                    </div>
                    @if ($this->asignacion->usuario?->cargo)
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel ml-5 mt-0.5">
                            {{ $this->asignacion->usuario->cargo->nombre }}
                        </p>
                    @endif
                @else
                    <div class="flex items-center gap-1.5">
                        <span class="material-icons text-sm text-cerberus-accent">corporate_fare</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $this->asignacion->areaDepartamento?->nombre ?? '—' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel ml-5 mt-0.5">
                        {{ $this->asignacion->areaEmpresa?->nombre ?? '—' }}
                        @if ($this->asignacion->areaResponsable)
                            · Resp: {{ $this->asignacion->areaResponsable->name }}
                        @endif
                    </p>
                @endif
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Estado</p>
                <x-asignaciones.badge-estado :estado="$this->asignacion->estado" />
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Fecha asignación</p>
                <p class="text-sm text-gray-900 dark:text-white">
                    {{ $this->asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Empresa</p>
                <p class="text-sm text-gray-900 dark:text-white">
                    {{ $this->asignacion->empresa?->nombre ?? '—' }}
                </p>
            </div>

        </div>
    </div>

    {{-- ── Aviso de huérfanos (aparece dinámicamente) ──────────────────────── --}}
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
                    Los siguientes equipos principales que vas a devolver tienen periféricos
                    asociados que <strong>no están seleccionados</strong>. Al confirmar, esos
                    periféricos se convertirán en equipos principales y seguirán asignados.
                    Puedes seleccionarlos ahora si también quieres devolverlos.
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
                                    <span class="text-amber-500/70">(periférico de {{ $principal->equipo?->codigo_interno ?? '—' }})</span>
                                </li>
                            @endif
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ── Lista de equipos activos ────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl overflow-hidden">

        {{-- Cabecera con seleccionar todos y contador --}}
        <div class="flex items-center justify-between px-6 py-4
                    border-b border-gray-100 dark:border-cerberus-steel/50">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox"
                       wire:model.live="seleccionarTodos"
                       class="w-4 h-4 rounded border-gray-300 dark:border-cerberus-steel
                              text-cerberus-primary bg-white dark:bg-cerberus-dark
                              focus:ring-cerberus-primary/30 transition" />
                <span class="text-sm font-medium text-gray-700 dark:text-cerberus-light">
                    Seleccionar todos
                </span>
            </label>

            <span class="text-xs text-gray-500 dark:text-cerberus-accent">
                {{ count($seleccionados) }} de {{ $this->todosLosItemsActivos->count() }} seleccionado(s)
                <span class="text-gray-300 dark:text-cerberus-steel/40 mx-1">·</span>
                {{ $this->todosLosItemsActivos->whereNull('equipo_padre_id')->count() }} principal(es),
                {{ $this->todosLosItemsActivos->whereNotNull('equipo_padre_id')->count() }} periférico(s)
            </span>
        </div>

        @error('seleccionados')
            <div class="px-6 py-2 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-700/30">
                <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                    <span class="material-icons text-xs">error_outline</span> {{ $message }}
                </p>
            </div>
        @enderror

        {{-- Items: principal + periféricos inmediatamente después --}}
        <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/30">

            @forelse ($this->todosLosItemsActivos as $item)

                @php
                    $esPeriférico      = $item->equipo_padre_id !== null;
                    $estaSeleccionado  = in_array((string) $item->id, $seleccionados);
                    $tieneHuerfanos    = ! $esPeriférico &&
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

                    <div class="flex items-start gap-4 px-6 py-4">

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

                                    {{-- Badge "quedarán libres" si es principal seleccionado con huérfanos --}}
                                    @if ($tieneHuerfanos)
                                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1
                                                   flex items-center gap-1">
                                            <span class="material-icons text-xs">warning_amber</span>
                                            Sus periféricos no seleccionados se promoverán a principales
                                        </p>
                                    @endif

                                    {{-- Si es principal activo, mostrar sus periféricos como referencia --}}
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

                            {{-- Textarea observación --}}
                            <div x-show="showObs" x-collapse class="mt-3">
                                <textarea wire:model="observaciones.{{ $item->id }}"
                                          rows="2"
                                          placeholder="Estado físico, motivo, observaciones..."
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

            @empty
                <div class="px-6 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-3xl block mb-2 opacity-40">inventory</span>
                    No hay equipos activos en esta asignación.
                </div>
            @endforelse

        </div>
    </div>

    {{-- ── Barra de acciones ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4
                bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl px-6 py-4">

        <div class="text-sm text-gray-600 dark:text-cerberus-light">
            @if (count($seleccionados) > 0)
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ count($seleccionados) }}
                </span>
                equipo(s) pasarán a
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded
                             bg-emerald-100 dark:bg-emerald-900/30
                             text-emerald-700 dark:text-emerald-400 text-xs font-medium">
                    <span class="material-icons text-xs">check_circle</span>
                    Disponible
                </span>
            @else
                <span class="text-gray-400 dark:text-cerberus-steel">Ningún equipo seleccionado</span>
            @endif
        </div>

        <div class="flex items-center gap-3">

            <a href="{{ route('admin.asignaciones.index') }}"
               wire:navigate
               class="px-4 py-2 rounded-lg text-sm
                      bg-gray-100 dark:bg-cerberus-dark text-gray-700 dark:text-cerberus-light
                      hover:bg-gray-200 dark:hover:bg-cerberus-steel/40
                      border border-gray-200 dark:border-cerberus-steel/40 transition">
                Cancelar
            </a>

            <button type="button"
                    wire:click="confirmar"
                    wire:loading.attr="disabled"
                    @disabled(empty($seleccionados))
                    class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium
                           bg-cerberus-primary hover:bg-cerberus-hover text-white transition
                           disabled:opacity-40 disabled:cursor-not-allowed">
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