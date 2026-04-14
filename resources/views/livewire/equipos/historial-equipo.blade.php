<div class="space-y-6">

    {{-- HEADER DEL EQUIPO --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">history</span>
                <div>
                    <h2 class="text-xl font-bold text-white">
                        Historial de cambios
                    </h2>
                    <p class="text-cerberus-light text-sm mt-0.5">
                        {{ $equipo->codigo_interno }}
                        @if($equipo->nombre_maquina)
                            · {{ $equipo->nombre_maquina }}
                        @endif
                        · <span class="text-cerberus-accent">{{ $equipo->categoria->nombre ?? '—' }}</span>
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.equipos.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-cerberus-dark border border-cerberus-steel
                      text-cerberus-light hover:text-white rounded-lg text-sm transition">
                <span class="material-icons text-sm">arrow_back</span>
                Volver al listado
            </a>
        </div>

        {{-- Datos clave del equipo --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5">
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Estado</p>
                <p class="text-white font-semibold text-sm">{{ $equipo->estado->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Ubicación</p>
                <p class="text-white font-semibold text-sm">{{ $equipo->ubicacion->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Empresa</p>
                <p class="text-white font-semibold text-sm">{{ $equipo->empresa->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Serial</p>
                <p class="text-white font-semibold text-sm">{{ $equipo->serial ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">
        <div class="flex flex-wrap gap-4 items-end">

            {{-- Filtro por atributo --}}
            <div>
                <label class="block text-cerberus-accent text-xs mb-1">Atributo</label>
                <select wire:model.live="atributo_id"
                    class="bg-cerberus-dark border border-cerberus-steel text-white text-sm rounded-lg px-3 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition min-w-[180px]">
                    <option value="">Todos los atributos</option>
                    @foreach($atributos as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Fecha desde --}}
            <div>
                <label class="block text-cerberus-accent text-xs mb-1">Desde</label>
                <input type="date" wire:model.live="fecha_desde"
                    class="bg-cerberus-dark border border-cerberus-steel text-white text-sm rounded-lg px-3 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition">
            </div>

            {{-- Fecha hasta --}}
            <div>
                <label class="block text-cerberus-accent text-xs mb-1">Hasta</label>
                <input type="date" wire:model.live="fecha_hasta"
                    class="bg-cerberus-dark border border-cerberus-steel text-white text-sm rounded-lg px-3 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition">
            </div>

            <button wire:click="resetFilters"
                class="px-3 py-2 bg-red-600/20 border border-red-700 text-red-300 text-sm rounded-lg
                       hover:bg-red-700/40 transition flex items-center gap-1">
                <span class="material-icons text-sm">filter_alt_off</span>
                Limpiar
            </button>
        </div>
    </div>

    {{-- TABLA DE HISTORIAL --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl overflow-hidden">

        @if($historial->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-cerberus-light">
                <span class="material-icons text-4xl mb-3 text-cerberus-steel">manage_search</span>
                <p class="text-sm">No hay registros de cambios para este equipo.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-cerberus-light">
                    <thead class="text-xs uppercase text-cerberus-accent bg-cerberus-darkest border-b border-cerberus-steel">
                        <tr>
                            <th class="px-5 py-3">Fecha y hora</th>
                            <th class="px-5 py-3">Atributo</th>
                            <th class="px-5 py-3">Valor registrado</th>
                            <th class="px-5 py-3">Modificado por</th>
                            <th class="px-5 py-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cerberus-steel">
                        @foreach($historial as $registro)
                            <tr wire:key="hist-{{ $registro->id }}"
                                class="hover:bg-cerberus-darkest transition
                                       {{ $registro->es_actual ? 'bg-cerberus-primary/5' : '' }}">

                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="text-white">
                                        {{ $registro->created_at?->format('d/m/Y') }}
                                    </span>
                                    <span class="text-cerberus-light text-xs block">
                                        {{ $registro->created_at?->format('H:i:s') }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 font-medium text-white">
                                    {{ $registro->atributo?->nombre ?? '—' }}
                                </td>

                                <td class="px-5 py-3">
                                    <span class="font-mono text-sm
                                        {{ $registro->es_actual ? 'text-cerberus-accent' : 'text-cerberus-light line-through decoration-cerberus-steel' }}">
                                        {{ $registro->valor ?? '—' }}
                                    </span>
                                </td>

                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons text-sm text-cerberus-steel">person</span>
                                        <span class="text-white">{{ $registro->usuario?->name ?? 'Sistema' }}</span>
                                    </div>
                                </td>

                                <td class="px-5 py-3 text-center">
                                    @if($registro->es_actual)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                                     bg-green-700/40 text-green-300 border border-green-700">
                                            <span class="material-icons text-xs">check_circle</span>
                                            Vigente
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                                     bg-cerberus-dark text-cerberus-light border border-cerberus-steel">
                                            <span class="material-icons text-xs">history</span>
                                            Histórico
                                        </span>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="px-5 py-4 border-t border-cerberus-steel">
                {{ $historial->links() }}
            </div>
        @endif

    </div>

</div>