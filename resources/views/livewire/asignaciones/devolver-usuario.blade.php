{{--
    livewire/asignaciones/devolver-usuario.blade.php
    Devolución unificada por usuario — todos sus equipos activos en una pantalla.
--}}
<div class="space-y-6">

    {{-- Error general --}}
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
            <img src="{{ $this->usuario->foto_url }}" alt="{{ $this->usuario->name }}"
                 class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-cerberus-steel/50">
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $this->usuario->name }}</p>
                <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                    {{ $this->usuario->empresaNomina?->nombre ?? '—' }} ·
                    {{ $this->usuario->cargo?->nombre ?? '—' }} ·
                    Ficha: {{ $this->usuario->ficha ?? '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── Panel principal ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl overflow-hidden">

        {{-- Cabecera con "seleccionar todos" --}}
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
                {{ count($seleccionados) }} de {{ $this->itemsActivos->count() }} seleccionado(s)
            </span>
        </div>

        @error('seleccionados')
            <div class="px-5 py-2 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-700/30">
                <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                    <span class="material-icons text-xs">error_outline</span> {{ $message }}
                </p>
            </div>
        @enderror

        {{-- Lista de equipos --}}
        <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/30" x-data>

            @forelse ($this->itemsActivos as $item)
                <div wire:key="item-{{ $item->id }}"
                     x-data="{ showObs: false }"
                     class="px-5 py-4 transition-colors duration-150
                            {{ in_array((string) $item->id, $seleccionados)
                                ? 'bg-cerberus-primary/5 dark:bg-cerberus-primary/10'
                                : 'hover:bg-gray-50 dark:hover:bg-cerberus-steel/5' }}">

                    <div class="flex items-start gap-4">

                        {{-- Checkbox --}}
                        <div class="pt-0.5 flex-shrink-0">
                            <input type="checkbox"
                                   wire:model.live="seleccionados"
                                   value="{{ $item->id }}"
                                   id="item-{{ $item->id }}"
                                   class="w-4 h-4 rounded border-gray-300 dark:border-cerberus-steel
                                          text-cerberus-primary bg-white dark:bg-cerberus-dark
                                          focus:ring-cerberus-primary/30 transition" />
                        </div>

                        {{-- Info del equipo --}}
                        <label for="item-{{ $item->id }}" class="flex-1 min-w-0 cursor-pointer">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $item->equipo?->codigo_interno ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-cerberus-accent mt-0.5">
                                        {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                        @if ($item->equipo?->serial)
                                            · S/N: {{ $item->equipo->serial }}
                                        @endif
                                        @if ($item->equipo?->nombre_maquina)
                                            · {{ $item->equipo->nombre_maquina }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                                        Asignado: {{ $item->asignacion?->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                    </p>
                                </div>

                                {{-- Badge estado equipo --}}
                                <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-xs
                                             rounded-full bg-emerald-100 dark:bg-emerald-900/30
                                             text-emerald-700 dark:text-emerald-400
                                             border border-emerald-200 dark:border-emerald-700/40">
                                    <span class="material-icons text-xs">check_circle</span>
                                    Activo
                                </span>
                            </div>

                            {{-- Periféricos anidados (informativos, se devuelven en cascada) --}}
                            @if ($item->hijos->isNotEmpty())
                                <div class="mt-2 space-y-0.5">
                                    @foreach ($item->hijos->where('devuelto', false) as $hijo)
                                        <div class="flex items-center gap-1.5 ml-1">
                                            <span class="material-icons text-xs text-gray-300 dark:text-cerberus-steel/50">
                                                subdirectory_arrow_right
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-cerberus-accent">
                                                {{ $hijo->equipo?->codigo_interno ?? '—' }}
                                                ({{ $hijo->equipo?->categoria?->nombre ?? '—' }})
                                                <span class="text-gray-400 dark:text-cerberus-steel">
                                                    — se devolverá en cascada
                                                </span>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </label>

                    </div>

                    {{-- Toggle observación --}}
                    <div class="ml-8 mt-2">
                        <button type="button" @click="showObs = !showObs"
                                class="flex items-center gap-1 text-xs text-gray-400 dark:text-cerberus-steel
                                       hover:text-cerberus-primary dark:hover:text-cerberus-accent transition">
                            <span class="material-icons text-xs"
                                  :style="showObs ? 'transform:rotate(90deg)' : ''"
                                  style="transition:transform 150ms">chevron_right</span>
                            <span x-text="showObs ? 'Ocultar observación' : 'Agregar observación'"></span>
                        </button>

                        <div x-show="showObs"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="mt-2" style="display:none">
                            <textarea wire:model="observaciones.{{ $item->id }}"
                                      placeholder="Observaciones de devolución (opcional)..."
                                      rows="2"
                                      class="w-full text-sm bg-white dark:bg-cerberus-dark
                                             border border-gray-200 dark:border-cerberus-steel/60
                                             text-gray-900 dark:text-white
                                             placeholder-gray-400 dark:placeholder-cerberus-accent/50
                                             rounded-lg px-3 py-2 focus:outline-none focus:ring-1
                                             focus:border-cerberus-primary focus:ring-cerberus-primary/30
                                             transition resize-none"></textarea>
                        </div>
                    </div>

                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <span class="material-icons text-4xl text-gray-200 dark:text-cerberus-steel/30 block mb-2">inventory</span>
                    <p class="text-sm text-gray-400 dark:text-cerberus-steel">
                        Este usuario no tiene equipos activos.
                    </p>
                </div>
            @endforelse

        </div>
    </div>

    {{-- ── Barra de acción ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4
                bg-white dark:bg-cerberus-mid
                border border-gray-200 dark:border-cerberus-steel
                rounded-xl px-5 py-4">

        <div class="text-sm text-gray-500 dark:text-cerberus-light">
            @if (count($seleccionados) > 0)
                <span class="font-semibold text-gray-900 dark:text-white">{{ count($seleccionados) }}</span>
                equipo(s) pasarán a
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs
                             bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 font-medium">
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