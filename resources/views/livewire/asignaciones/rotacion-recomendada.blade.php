<div class="space-y-6">

    {{-- HEADER --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">autorenew</span>
                <div>
                    <h2 class="text-xl font-bold text-cerberus-light">
                        Rotación de asignaciones recomendada
                    </h2>
                    <p class="text-cerberus-light text-sm mt-0.5 max-w-2xl">
                        Asignaciones activas que ya superaron el periodo de permanencia recomendado
                        para su categoría. <span class="text-cerberus-accent">No indica que el equipo
                        esté dañado ni obsoleto</span> — es solo una referencia de cuánto tiempo lleva
                        la misma persona con el mismo equipo, para evaluar si conviene rotarlo.
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.asignaciones.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-cerberus-dark border border-cerberus-steel
                      text-cerberus-light hover:text-cerberus-accent rounded-lg text-sm transition">
                <span class="material-icons text-sm">arrow_back</span>
                Volver a Asignaciones
            </a>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">
        <div class="flex flex-wrap gap-4 items-start">
            <div class="min-w-[180px]">
                <x-form.select label="Empresa" placeholder="Todas" :options="$this->empresasOpciones" wire:model.live="empresaId" />
            </div>

            <div class="min-w-[180px]">
                <x-form.select label="Categoría" placeholder="Todas" :options="$this->categoriasOpciones" wire:model.live="categoriaId" />
            </div>

            <button wire:click="resetFiltros"
                class="px-3 py-2 bg-red-600/20 border border-red-700 text-red-300 text-sm rounded-lg
                       hover:bg-red-700/40 transition flex items-center gap-1">
                <span class="material-icons text-sm">filter_alt_off</span>
                Limpiar
            </button>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl overflow-hidden">
        @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-cerberus-light">
                <span class="material-icons text-4xl mb-3 text-emerald-400/60">check_circle</span>
                <p class="text-sm">No hay asignaciones que superen su periodo de rotación recomendado.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-cerberus-accent text-xs bg-cerberus-darkest/30">
                            <th class="px-5 py-2.5 text-left font-medium">Receptor</th>
                            <th class="px-3 py-2.5 text-left font-medium">Equipo</th>
                            <th class="px-3 py-2.5 text-left font-medium hidden sm:table-cell">Categoría</th>
                            <th class="px-3 py-2.5 text-left font-medium hidden md:table-cell">Empresa</th>
                            <th class="px-3 py-2.5 text-center font-medium">Permanencia</th>
                            <th class="px-4 py-2.5 text-right font-medium">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cerberus-steel/40">
                        @foreach($items as $item)
                            @php
                                $meses  = $item->mesesConReceptor();
                                $umbral = $item->mesesRotacionRecomendada();
                            @endphp
                            <tr class="hover:bg-cerberus-darkest/20 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
                                            <span class="material-icons text-red-400 text-sm">person</span>
                                        </div>
                                        <span class="text-gray-900 dark:text-white text-sm font-medium truncate max-w-[150px]">
                                            {{ $item->asignacion->usuario->name ?? '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-cerberus-light text-sm font-mono">
                                    {{ $item->equipo->codigo_interno ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-cerberus-accent text-xs hidden sm:table-cell">
                                    {{ $item->equipo->categoria->nombre ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-cerberus-accent text-xs hidden md:table-cell">
                                    {{ $item->asignacion->empresa->nombre ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="bg-red-500/20 text-red-400 text-xs font-bold rounded px-2 py-0.5">
                                        {{ $meses }}m / {{ $umbral }}m
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($item->asignacion->usuario_id)
                                        <a href="{{ route('admin.usuarios.trazabilidad', $item->asignacion->usuario_id) }}"
                                           class="text-cerberus-light hover:text-gray-900 dark:hover:text-white text-xs flex items-center justify-end gap-1 transition-colors">
                                            Ver <span class="material-icons text-sm">open_in_new</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-cerberus-steel">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>
