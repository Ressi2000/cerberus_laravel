<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-5xl bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel
                        rounded-xl shadow-xl flex flex-col"
                 style="max-height: 92vh;">

                {{-- ── CABECERA ──────────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-6 py-4 flex-shrink-0
                            border-b border-gray-100 dark:border-cerberus-steel">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent">tune</span>
                            Atributos de «{{ $categoriaNombre }}»
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-cerberus-light mt-0.5">
                            Edita, agrega o elimina atributos. Los cambios se aplican al guardar.
                        </p>
                    </div>
                    <button wire:click="close"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition flex-shrink-0">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- ── LEYENDA DE COLUMNAS (sticky) ─────────────────────────────── --}}
                <div class="flex-shrink-0 px-6 pt-3 pb-2
                            bg-gray-50/80 dark:bg-cerberus-dark/40
                            border-b border-gray-100 dark:border-cerberus-steel/30">
                    <div class="grid items-center gap-2 text-xs font-semibold
                                text-gray-500 dark:text-cerberus-accent uppercase tracking-wide"
                         style="grid-template-columns: 2fr 1.2fr 40px 40px 40px 60px 32px 32px;">
                        <span>Nombre del atributo</span>
                        <span>Tipo de dato</span>
                        <span class="text-center" title="Requerido">Req.</span>
                        <span class="text-center" title="Filtrable">Filt.</span>
                        <span class="text-center" title="Visible en tabla">Tab.</span>
                        <span class="text-center">Orden</span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                {{-- ── FILAS DE ATRIBUTOS (scroll) ──────────────────────────────── --}}
                <div class="overflow-y-auto flex-1 px-6 py-3 space-y-2">

                    @forelse ($filas as $i => $fila)
                        <div wire:key="fila-{{ $fila['uid'] }}"
                             class="rounded-xl border transition-all duration-150
                                    {{ $fila['eliminar']
                                        ? 'bg-red-50 dark:bg-red-900/15 border-red-200 dark:border-red-700/40 opacity-60'
                                        : 'bg-white dark:bg-cerberus-dark/40 border-gray-200 dark:border-cerberus-steel/40' }}">

                            {{-- Fila principal --}}
                            <div class="grid items-center gap-2 px-3 py-2.5"
                                 style="grid-template-columns: 2fr 1.2fr 40px 40px 40px 60px 32px 32px;">

                                {{-- Nombre --}}
                                <div>
                                    <input wire:model="filas.{{ $i }}.nombre"
                                        type="text"
                                        placeholder="Ej: RAM (GB), Procesador..."
                                        {{ $fila['eliminar'] ? 'disabled' : '' }}
                                        class="w-full rounded-lg px-3 py-1.5 text-sm
                                               bg-white dark:bg-cerberus-dark
                                               border border-gray-300 dark:border-cerberus-steel
                                               text-gray-900 dark:text-white
                                               placeholder-gray-400 dark:placeholder-gray-500
                                               focus:outline-none focus:ring-2
                                               focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                               dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                               disabled:opacity-50 disabled:cursor-not-allowed
                                               @error('filas.'.$i.'.nombre') border-red-400 @enderror"
                                    />
                                    @error('filas.'.$i.'.nombre')
                                        <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Tipo --}}
                                <div>
                                    <select wire:model.live="filas.{{ $i }}.tipo"
                                        {{ $fila['eliminar'] ? 'disabled' : '' }}
                                        class="w-full rounded-lg px-3 py-1.5 text-sm
                                               bg-white dark:bg-cerberus-dark
                                               border border-gray-300 dark:border-cerberus-steel
                                               text-gray-900 dark:text-white
                                               focus:outline-none focus:ring-2
                                               focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                               dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                               disabled:opacity-50 disabled:cursor-not-allowed">
                                        @foreach ($tiposDisponibles as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Toggle: Requerido --}}
                                <div class="flex justify-center">
                                    <button wire:click="$set('filas.{{ $i }}.requerido', {{ $fila['requerido'] ? 'false' : 'true' }})"
                                        type="button"
                                        {{ $fila['eliminar'] ? 'disabled' : '' }}
                                        title="Requerido"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition
                                               {{ $fila['requerido']
                                                   ? 'bg-[#1E40AF]/15 text-[#1E40AF] dark:bg-cerberus-primary/20 dark:text-cerberus-accent'
                                                   : 'text-gray-300 dark:text-cerberus-steel/40 hover:text-gray-400' }}
                                               disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span class="material-icons text-base">
                                            {{ $fila['requerido'] ? 'star' : 'star_border' }}
                                        </span>
                                    </button>
                                </div>

                                {{-- Toggle: Filtrable --}}
                                <div class="flex justify-center">
                                    <button wire:click="$set('filas.{{ $i }}.filtrable', {{ $fila['filtrable'] ? 'false' : 'true' }})"
                                        type="button"
                                        {{ $fila['eliminar'] ? 'disabled' : '' }}
                                        title="Filtrable"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition
                                               {{ $fila['filtrable']
                                                   ? 'bg-green-50 dark:bg-green-500/15 text-green-600 dark:text-green-400'
                                                   : 'text-gray-300 dark:text-cerberus-steel/40 hover:text-gray-400' }}
                                               disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span class="material-icons text-base">filter_list</span>
                                    </button>
                                </div>

                                {{-- Toggle: Visible en tabla --}}
                                <div class="flex justify-center">
                                    <button wire:click="$set('filas.{{ $i }}.visible_en_tabla', {{ $fila['visible_en_tabla'] ? 'false' : 'true' }})"
                                        type="button"
                                        {{ $fila['eliminar'] ? 'disabled' : '' }}
                                        title="Visible en tabla"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition
                                               {{ $fila['visible_en_tabla']
                                                   ? 'bg-purple-50 dark:bg-purple-500/15 text-purple-600 dark:text-purple-400'
                                                   : 'text-gray-300 dark:text-cerberus-steel/40 hover:text-gray-400' }}
                                               disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span class="material-icons text-base">table_chart</span>
                                    </button>
                                </div>

                                {{-- Orden --}}
                                <div>
                                    <input wire:model="filas.{{ $i }}.orden"
                                        type="number" min="0"
                                        {{ $fila['eliminar'] ? 'disabled' : '' }}
                                        class="w-full rounded-lg px-2 py-1.5 text-sm text-center
                                               bg-white dark:bg-cerberus-dark
                                               border border-gray-300 dark:border-cerberus-steel
                                               text-gray-900 dark:text-white
                                               focus:outline-none focus:ring-2
                                               focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                               dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                               disabled:opacity-50 disabled:cursor-not-allowed"
                                    />
                                </div>

                                {{-- Indicador tiene_valores --}}
                                <div class="flex justify-center">
                                    @if ($fila['tiene_valores'])
                                        <span class="material-icons text-base text-amber-500 dark:text-amber-400"
                                              title="Tiene valores registrados en equipos. No se puede eliminar.">
                                            lock
                                        </span>
                                    @elseif ($fila['id'])
                                        <span class="material-icons text-base text-gray-300 dark:text-cerberus-steel/30"
                                              title="Sin valores en equipos. Se puede eliminar.">
                                            lock_open
                                        </span>
                                    @else
                                        <span class="material-icons text-base text-[#1E40AF]/40 dark:text-cerberus-primary/40"
                                              title="Atributo nuevo">
                                            fiber_new
                                        </span>
                                    @endif
                                </div>

                                {{-- Botón eliminar/restaurar fila --}}
                                <div class="flex justify-center">
                                    <button wire:click="toggleEliminar('{{ $fila['uid'] }}')"
                                        type="button"
                                        {{ ($fila['tiene_valores'] && !$fila['eliminar']) ? 'disabled' : '' }}
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition
                                               {{ $fila['eliminar']
                                                   ? 'bg-gray-100 dark:bg-cerberus-steel/20 text-gray-500 dark:text-cerberus-light hover:bg-gray-200'
                                                   : 'text-red-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10' }}
                                               disabled:opacity-30 disabled:cursor-not-allowed">
                                        <span class="material-icons text-base">
                                            {{ $fila['eliminar'] ? 'restore' : 'delete' }}
                                        </span>
                                    </button>
                                </div>

                            </div>

                            {{-- Panel de opciones para tipo 'select' --}}
                            @if ($fila['tipo'] === 'select' && !$fila['eliminar'])
                                <div class="px-3 pb-3 pt-0 border-t border-gray-100 dark:border-cerberus-steel/20">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-cerberus-accent mt-2 mb-1">
                                        <span class="material-icons text-xs align-middle">list</span>
                                        Opciones (una por línea)
                                        <span class="text-red-400">*</span>
                                    </label>
                                    <textarea wire:model="filas.{{ $i }}.opciones_raw"
                                        rows="3"
                                        placeholder="Opción 1&#10;Opción 2&#10;Opción 3"
                                        class="w-full rounded-lg px-3 py-2 text-sm resize-none
                                               bg-white dark:bg-cerberus-dark
                                               border border-gray-300 dark:border-cerberus-steel
                                               text-gray-900 dark:text-white
                                               placeholder-gray-400 dark:placeholder-gray-500
                                               focus:outline-none focus:ring-2
                                               focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                               dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                                               @error('filas.'.$i.'.opciones_raw') border-red-400 @enderror">
                                    </textarea>
                                    @error('filas.'.$i.'.opciones_raw')
                                        <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-400 dark:text-cerberus-steel">
                            <span class="material-icons text-3xl block mb-2">tune</span>
                            <p class="text-sm">No hay atributos. Pulsa «Agregar fila» para empezar.</p>
                        </div>
                    @endforelse

                </div>

                {{-- ── FOOTER ───────────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-6 py-4 flex-shrink-0
                            border-t border-gray-100 dark:border-cerberus-steel">

                    {{-- Botón agregar fila --}}
                    <button wire:click="agregarFila" type="button"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg font-medium
                               text-[#1E40AF] dark:text-cerberus-accent
                               bg-[#1E40AF]/10 dark:bg-cerberus-primary/10
                               hover:bg-[#1E40AF]/20 dark:hover:bg-cerberus-primary/20
                               transition">
                        <span class="material-icons text-base">add</span>
                        Agregar fila
                    </button>

                    {{-- Leyenda de iconos --}}
                    <div class="hidden sm:flex items-center gap-4 text-xs text-gray-400 dark:text-cerberus-steel">
                        <span class="flex items-center gap-1">
                            <span class="material-icons text-sm text-[#1E40AF]/60 dark:text-cerberus-accent">star</span>
                            Requerido
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-icons text-sm text-green-500">filter_list</span>
                            Filtrable
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-icons text-sm text-purple-500">table_chart</span>
                            Visible tabla
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-icons text-sm text-amber-500">lock</span>
                            Con valores
                        </span>
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center gap-3">
                        <button wire:click="close"
                            class="px-4 py-2 text-sm rounded-lg
                                   bg-gray-100 dark:bg-cerberus-steel/30
                                   text-gray-700 dark:text-white
                                   hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                            Cancelar
                        </button>
                        <button wire:click="guardar" wire:loading.attr="disabled"
                            class="px-5 py-2 text-sm rounded-lg font-medium
                                   bg-[#1E40AF] hover:bg-[#1E3A8A] text-white
                                   transition flex items-center gap-2 disabled:opacity-60">
                            <span wire:loading.remove wire:target="guardar"
                                  class="material-icons text-sm">save</span>
                            <span wire:loading wire:target="guardar"
                                  class="material-icons text-sm animate-spin">refresh</span>
                            <span wire:loading.remove wire:target="guardar">Guardar todo</span>
                            <span wire:loading wire:target="guardar">Guardando...</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>