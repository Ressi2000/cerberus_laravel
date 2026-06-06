<div class="space-y-6">

    @error('general')
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg
                    bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50
                    text-red-700 dark:text-red-300 text-sm">
            <span class="material-icons text-base flex-shrink-0">error_outline</span>
            {{ $message }}
        </div>
    @enderror

    {{-- ── Cabecera del préstamo ────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6">

        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-cerberus-steel mb-4">
            Préstamo a devolver
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Receptor</p>
                @if ($this->prestamo->usuario_id)
                    <div class="flex items-center gap-1.5">
                        <span class="material-icons text-sm text-cerberus-accent">person</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->prestamo->usuario?->name ?? '—' }}</span>
                    </div>
                    @if ($this->prestamo->usuario?->cargo)
                        <p class="text-xs text-gray-500 dark:text-cerberus-steel ml-5 mt-0.5">{{ $this->prestamo->usuario->cargo->nombre }}</p>
                    @endif
                @else
                    <div class="flex items-center gap-1.5">
                        <span class="material-icons text-sm text-cerberus-accent">corporate_fare</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->prestamo->areaDepartamento?->nombre ?? '—' }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-cerberus-steel ml-5 mt-0.5">
                        {{ $this->prestamo->areaEmpresa?->nombre ?? '—' }}
                        @if ($this->prestamo->areaResponsable) · Resp: {{ $this->prestamo->areaResponsable->name }} @endif
                    </p>
                @endif
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Estado</p>
                <x-prestamos.badge-estado :estado="$this->prestamo->estado" />
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Fecha de préstamo</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $this->prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}</p>
                @if ($this->prestamo->fecha_devolucion_esperada)
                    <p class="text-xs mt-0.5 {{ $this->prestamo->estaVencido() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                        Devolver: {{ $this->prestamo->fecha_devolucion_esperada->format('d/m/Y') }}
                    </p>
                @endif
            </div>

            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-accent mb-0.5">Empresa</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $this->prestamo->empresa?->nombre ?? '—' }}</p>
            </div>

        </div>
    </div>

    {{-- ── Aviso de huérfanos ─────────────────────────────────────────────── --}}
    @if ($this->principalesConHuerfanos->isNotEmpty())
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl
                    bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-600/50">
            <span class="material-icons text-amber-500 flex-shrink-0 mt-0.5">info</span>
            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-1">Periféricos que quedarán libres</p>
                <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                    Al devolver
                    @foreach ($this->principalesConHuerfanos as $prin)
                        <strong>{{ $prin->equipo?->codigo_interno }}</strong>{{ ! $loop->last ? ', ' : '' }}
                    @endforeach
                    , sus periféricos activos no seleccionados se promoverán a equipos principales dentro del mismo préstamo.
                    Podrás devolverlos de forma independiente después.
                </p>
            </div>
        </div>
    @endif

    {{-- ── Lista de items ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-cerberus-steel/40">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="seleccionarTodos"
                           class="rounded border-gray-300 dark:border-cerberus-steel
                                  text-cerberus-primary focus:ring-cerberus-primary/50">
                    <span class="text-sm font-medium text-gray-700 dark:text-cerberus-light">Seleccionar todos</span>
                </label>
                @if (count($seleccionados) > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                 bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                                 text-cerberus-primary dark:text-cerberus-accent">
                        {{ count($seleccionados) }} seleccionado(s)
                    </span>
                @endif
            </div>
            <span class="text-xs text-gray-400 dark:text-cerberus-steel">
                {{ $this->todosLosItemsActivos->count() }} equipo(s) activo(s)
            </span>
        </div>

        @if ($this->todosLosItemsActivos->isEmpty())
            <div class="px-5 py-12 text-center">
                <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel/30 block mb-2">inventory_2</span>
                <p class="text-sm text-gray-500 dark:text-cerberus-accent">No hay equipos activos en este préstamo.</p>
            </div>
        @else
            <div class="divide-y divide-gray-50 dark:divide-cerberus-steel/20">
                @foreach ($this->todosLosItemsActivos as $item)
                    @php $esPeriferico = $item->equipo_padre_id !== null; @endphp
                    <div wire:key="item-{{ $item->id }}"
                         class="px-5 py-4 {{ $esPeriferico ? 'pl-12 bg-gray-50/50 dark:bg-cerberus-dark/30' : '' }}
                                hover:bg-gray-50 dark:hover:bg-cerberus-steel/5 transition-colors">

                        <div class="flex items-start gap-4">

                            {{-- Checkbox --}}
                            <label class="flex items-center mt-0.5 cursor-pointer flex-shrink-0">
                                <input type="checkbox" wire:model.live="seleccionados" value="{{ $item->id }}"
                                       class="rounded border-gray-300 dark:border-cerberus-steel
                                              text-cerberus-primary focus:ring-cerberus-primary/50">
                            </label>

                            {{-- Info del equipo --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if ($esPeriferico)
                                        <span class="text-gray-400 dark:text-cerberus-steel text-xs">↳ periférico</span>
                                    @endif
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $item->equipo?->codigo_interno ?? '—' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-xs
                                                 bg-gray-100 dark:bg-cerberus-dark
                                                 text-gray-600 dark:text-cerberus-light">
                                        {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                    </span>
                                    @if ($esPeriferico && $item->padre)
                                        <span class="text-xs text-gray-400 dark:text-cerberus-steel">
                                            de {{ $item->padre->equipo?->codigo_interno ?? '—' }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-0.5">
                                    S/N: {{ $item->equipo?->serial ?? '—' }}
                                    @if ($item->equipo?->nombre_maquina) · {{ $item->equipo->nombre_maquina }} @endif
                                </p>
                            </div>

                            {{-- Campo observaciones (visible si seleccionado) --}}
                            @if (in_array((string) $item->id, $seleccionados))
                                <div class="w-64 flex-shrink-0">
                                    <input type="text"
                                           wire:model.defer="observaciones.{{ $item->id }}"
                                           placeholder="Observaciones (opcional)"
                                           class="w-full text-xs rounded-lg
                                                  border border-gray-200 dark:border-cerberus-steel
                                                  bg-white dark:bg-cerberus-dark
                                                  text-gray-700 dark:text-cerberus-light
                                                  placeholder-gray-400 dark:placeholder-cerberus-steel
                                                  focus:ring-1 focus:ring-cerberus-primary/50
                                                  px-3 py-2 transition">
                                </div>
                            @endif

                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- ── Error selección ─────────────────────────────────────────────────── --}}
    @error('seleccionados')
        <p class="text-sm text-red-500">{{ $message }}</p>
    @enderror

    {{-- ── Acciones ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 justify-end">
        <a href="{{ route('admin.prestamos.index') }}" wire:navigate
           class="px-5 py-2.5 rounded-xl text-sm font-medium
                  bg-gray-100 dark:bg-cerberus-dark
                  text-gray-700 dark:text-cerberus-light
                  hover:bg-gray-200 dark:hover:bg-cerberus-steel/30 transition">
            Cancelar
        </a>

        <button wire:click="confirmar"
                wire:loading.attr="disabled"
                :disabled="{{ count($seleccionados) === 0 ? 'true' : 'false' }}"
                class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold
                       bg-amber-500 hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed
                       text-white shadow-sm transition">
            <span class="material-icons text-base">keyboard_return</span>
            <span wire:loading.remove wire:target="confirmar">
                Confirmar devolución
                @if (count($seleccionados) > 0)({{ count($seleccionados) }})@endif
            </span>
            <span wire:loading wire:target="confirmar">Procesando...</span>
        </button>
    </div>

</div>
