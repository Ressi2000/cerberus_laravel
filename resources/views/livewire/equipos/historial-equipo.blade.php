<div class="space-y-6">

    {{-- HEADER DEL EQUIPO --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">history</span>
                <div>
                    <h2 class="text-xl font-bold text-cerberus-light">
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
                      text-cerberus-light hover:text-cerberus-accent rounded-lg text-sm transition">
                <span class="material-icons text-sm">arrow_back</span>
                Volver al listado
            </a>
        </div>

        {{-- Datos clave del equipo --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5">
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Estado</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $equipo->estado->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Ubicación</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $equipo->ubicacion->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Empresa</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $equipo->empresa->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Serial</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $equipo->serial ?? '—' }}</p>
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
                    class="bg-cerberus-dark border border-cerberus-steel text-cerberus-light text-sm rounded-lg px-3 py-2
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
                    class="bg-cerberus-dark border border-cerberus-steel text-cerberus-light text-sm rounded-lg px-3 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition">
            </div>

            {{-- Fecha hasta --}}
            <div>
                <label class="block text-cerberus-accent text-xs mb-1">Hasta</label>
                <input type="date" wire:model.live="fecha_hasta"
                    class="bg-cerberus-dark border border-cerberus-steel text-cerberus-light text-sm rounded-lg px-3 py-2
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
                                    <span class="text-cerberus-light">
                                        {{ $registro->created_at?->format('d/m/Y') }}
                                    </span>
                                    <span class="text-cerberus-light text-xs block">
                                        {{ $registro->created_at?->format('H:i:s') }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 font-medium text-cerberus-light">
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
                                        <span class="text-cerberus-light">{{ $registro->usuario?->name ?? 'Sistema' }}</span>
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

    {{-- ══ HISTORIAL DE TRASLADOS ══════════════════════════════════════════════ --}}
    @if ($traslados->isNotEmpty())
        <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl overflow-hidden">

            <div class="px-5 py-4 border-b border-cerberus-steel flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-lg">local_shipping</span>
                <h3 class="text-base font-semibold text-cerberus-light">
                    Historial de Traslados
                </h3>
                <span class="ml-auto px-2.5 py-0.5 rounded-full text-xs font-bold
                             bg-cerberus-primary/20 text-cerberus-accent">
                    {{ $traslados->count() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-cerberus-steel bg-cerberus-dark">
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-cerberus-light">N°</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-cerberus-light">Fecha</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-cerberus-light">Origen</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-cerberus-light">Destino</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-cerberus-light">Recibe</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-cerberus-light">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cerberus-steel/50">
                        @foreach ($traslados as $item)
                            <tr class="hover:bg-cerberus-dark/40 transition-colors">
                                <td class="px-5 py-3">
                                    <span class="font-semibold text-cerberus-accent">
                                        {{ $item->traslado?->numero ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-cerberus-light">
                                    {{ $item->traslado?->fecha_traslado?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-700/40 text-slate-300">
                                        {{ $item->traslado?->ubicacionOrigen?->nombre ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-900/30 text-blue-300">
                                        {{ $item->traslado?->ubicacionDestino?->nombre ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-cerberus-light">
                                    {{ $item->traslado?->recibe?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.traslados.show', $item->traslado) }}"
                                        class="flex items-center gap-1 text-xs text-cerberus-accent hover:underline">
                                        <span class="material-icons text-sm">visibility</span>
                                        Ver traslado
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>