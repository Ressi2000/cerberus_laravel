{{--
    resources/views/livewire/asignaciones/devolver-asignacion.blade.php

    Formulario de devolución total o parcial.
    Lista de checkboxes: uno por equipo activo.
    Cada item puede llevar una observación individual opcional.
    Alpine maneja el toggle del textarea de observación para no saturar el DOM.
--}}
<div class="space-y-6">

    {{-- ── ERROR GENERAL ───────────────────────────────────────────────────── --}}
    @error('general')
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg
                    bg-red-50 dark:bg-red-900/30
                    border border-red-200 dark:border-red-700/50
                    text-red-700 dark:text-red-300 text-sm">
            <span class="material-icons text-base flex-shrink-0">error_outline</span>
            {{ $message }}
        </div>
    @enderror

    {{-- ── CABECERA DE LA ASIGNACIÓN ───────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl p-6">

        <h2 class="text-sm font-semibold uppercase tracking-wider
                   text-gray-400 dark:text-cerberus-steel mb-4">
            Asignación a devolver
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Receptor</p>
                <div class="flex items-center gap-1.5">
                    <span class="material-icons text-sm text-cerberus-accent">
                        {{ $this->asignacion->usuario_id ? 'person' : 'location_on' }}
                    </span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $this->asignacion->receptorNombre() }}
                    </span>
                </div>
                @if ($this->asignacion->usuario?->cargo)
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel ml-5 mt-0.5">
                        {{ $this->asignacion->usuario->cargo->nombre }}
                    </p>
                @endif
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Estado actual</p>
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

    {{-- ── LISTA DE EQUIPOS ACTIVOS ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl overflow-hidden">

        {{-- Cabecera de sección con "seleccionar todos" --}}
        <div class="flex items-center justify-between px-6 py-4
                    border-b border-gray-100 dark:border-cerberus-steel/50">

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        wire:model.live="seleccionarTodos"
                        class="w-4 h-4 rounded border-gray-300 dark:border-cerberus-steel
                               text-cerberus-primary bg-white dark:bg-cerberus-dark
                               focus:ring-cerberus-primary/30 transition"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-cerberus-light">
                        Seleccionar todos
                    </span>
                </label>
            </div>

            <span class="text-xs text-gray-500 dark:text-cerberus-accent">
                {{ count($seleccionados) }} de {{ $this->asignacion->itemsActivos->count() }} seleccionado(s)
            </span>
        </div>

        @error('seleccionados')
            <div class="px-6 py-2 bg-red-50 dark:bg-red-900/20
                        border-b border-red-200 dark:border-red-700/30">
                <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                    <span class="material-icons text-xs">error_outline</span>
                    {{ $message }}
                </p>
            </div>
        @enderror

        {{-- Items --}}
        <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/30">

            @forelse ($this->asignacion->itemsActivos as $item)

                <div
                    wire:key="item-{{ $item->id }}"
                    x-data="{ showObs: false }"
                    class="px-6 py-4 transition-colors duration-150
                           {{ in_array((string) $item->id, $seleccionados)
                               ? 'bg-cerberus-primary/5 dark:bg-cerberus-primary/10'
                               : 'hover:bg-gray-50 dark:hover:bg-cerberus-steel/5' }}"
                >

                    <div class="flex items-start gap-4">

                        {{-- Checkbox --}}
                        <div class="pt-0.5">
                            <input
                                type="checkbox"
                                wire:model.live="seleccionados"
                                value="{{ $item->id }}"
                                id="item-{{ $item->id }}"
                                class="w-4 h-4 rounded border-gray-300 dark:border-cerberus-steel
                                       text-cerberus-primary bg-white dark:bg-cerberus-dark
                                       focus:ring-cerberus-primary/30 transition"
                            />
                        </div>

                        {{-- Datos del equipo --}}
                        <label for="item-{{ $item->id }}" class="flex-1 cursor-pointer min-w-0">
                            <div class="flex items-start justify-between gap-3">

                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $item->equipo?->codigo_interno ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-0.5 truncate">
                                        {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                        @if ($item->equipo?->serial)
                                            · S/N: {{ $item->equipo->serial }}
                                        @endif
                                        @if ($item->equipo?->nombre_maquina)
                                            · {{ $item->equipo->nombre_maquina }}
                                        @endif
                                    </p>
                                </div>

                                {{-- Estado del equipo --}}
                                <span class="flex-shrink-0 inline-flex items-center gap-1
                                             px-2 py-0.5 text-xs rounded-full
                                             bg-emerald-100 dark:bg-emerald-900/30
                                             text-emerald-700 dark:text-emerald-400
                                             border border-emerald-200 dark:border-emerald-700/40">
                                    <span class="material-icons text-xs">check_circle</span>
                                    {{ $item->equipo?->estado?->nombre ?? 'Asignado' }}
                                </span>

                            </div>
                        </label>

                    </div>

                    {{-- Toggle observación individual --}}
                    <div class="ml-8 mt-2">
                        <button
                            type="button"
                            @click="showObs = !showObs"
                            class="flex items-center gap-1 text-xs
                                   text-gray-400 dark:text-cerberus-steel
                                   hover:text-cerberus-primary dark:hover:text-cerberus-accent
                                   transition-colors duration-150">
                            <span class="material-icons text-xs"
                                  :class="showObs ? 'rotate-90' : ''"
                                  style="transition: transform 150ms ease">
                                chevron_right
                            </span>
                            <span x-text="showObs ? 'Ocultar observación' : 'Agregar observación'"></span>
                        </button>

                        <div
                            x-show="showObs"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="mt-2"
                            style="display: none"
                        >
                            <textarea
                                wire:model="observaciones.{{ $item->id }}"
                                placeholder="Observaciones de la devolución (opcional)..."
                                rows="2"
                                class="w-full text-sm bg-white dark:bg-cerberus-dark
                                       border border-gray-200 dark:border-cerberus-steel/60
                                       text-gray-900 dark:text-white
                                       placeholder-gray-400 dark:placeholder-cerberus-accent/50
                                       rounded-lg px-3 py-2
                                       focus:outline-none focus:ring-1
                                       focus:border-cerberus-primary focus:ring-cerberus-primary/30
                                       transition resize-none"
                            ></textarea>
                            @error("observaciones.{$item->id}")
                                <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
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

    {{-- ── BOTONES DE ACCIÓN ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4
                bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl px-6 py-4">

        {{-- Resumen de lo que se va a devolver --}}
        <div class="text-sm text-gray-600 dark:text-cerberus-light">
            @if (count($seleccionados) > 0)
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ count($seleccionados) }}
                </span>
                equipo(s) pasarán a estado
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded
                             bg-emerald-100 dark:bg-emerald-900/30
                             text-emerald-700 dark:text-emerald-400 text-xs font-medium">
                    <span class="material-icons text-xs">check_circle</span>
                    Disponible
                </span>
            @else
                <span class="text-gray-400 dark:text-cerberus-steel">
                    Ningún equipo seleccionado
                </span>
            @endif
        </div>

        <div class="flex items-center gap-3">

            {{-- Cancelar --}}
            <a href="{{ route('admin.asignaciones.index') }}"
               wire:navigate
               class="px-4 py-2 rounded-lg text-sm
                      bg-gray-100 dark:bg-cerberus-dark
                      text-gray-700 dark:text-cerberus-light
                      hover:bg-gray-200 dark:hover:bg-cerberus-steel/40
                      border border-gray-200 dark:border-cerberus-steel/40
                      transition-colors duration-150">
                Cancelar
            </a>

            {{-- Confirmar devolución --}}
            <button
                type="button"
                wire:click="confirmar"
                wire:loading.attr="disabled"
                @disabled(empty($seleccionados))
                class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium
                       bg-cerberus-primary hover:bg-cerberus-hover
                       text-white transition-colors duration-150
                       disabled:opacity-40 disabled:cursor-not-allowed">

                <span wire:loading.remove wire:target="confirmar">
                    <span class="material-icons text-base">keyboard_return</span>
                </span>
                <span wire:loading wire:target="confirmar">
                    <span class="material-icons text-base animate-spin">refresh</span>
                </span>

                <span wire:loading.remove wire:target="confirmar">
                    Confirmar devolución
                    @if (count($seleccionados) > 0)
                        ({{ count($seleccionados) }})
                    @endif
                </span>
                <span wire:loading wire:target="confirmar">Procesando...</span>

            </button>

        </div>
    </div>

</div>