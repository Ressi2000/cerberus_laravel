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
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-5">
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
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">
        <div class="flex flex-wrap gap-4 items-end">

            {{-- Filtro por tipo de evento --}}
            <div>
                <label class="block text-cerberus-accent text-xs mb-1">Tipo de evento</label>
                <select wire:model.live="tipo"
                    class="bg-cerberus-dark border border-cerberus-steel text-cerberus-light text-sm rounded-lg px-3 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition min-w-[160px]">
                    <option value="">Todos</option>
                    <option value="tecnico">Técnico</option>
                    <option value="asignacion">Asignación</option>
                    <option value="traslado">Traslado</option>
                    <option value="prestamo">Préstamo</option>
                    <option value="estado">Estado</option>
                </select>
            </div>

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

    {{-- TIMELINE CONSOLIDADO --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl overflow-hidden">

        @if($eventos->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-cerberus-light">
                <span class="material-icons text-4xl mb-3 text-cerberus-steel">manage_search</span>
                <p class="text-sm">No hay eventos registrados para este equipo.</p>
            </div>
        @else
            <div class="p-5">
                <ul class="space-y-4">
                    @php
                        $colorClases = [
                            'indigo' => 'bg-indigo-700/20 border-indigo-700/40 text-indigo-300',
                            'blue'   => 'bg-blue-700/20 border-blue-700/40 text-blue-300',
                            'sky'    => 'bg-sky-700/20 border-sky-700/40 text-sky-300',
                            'amber'  => 'bg-amber-700/20 border-amber-700/40 text-amber-300',
                            'yellow' => 'bg-yellow-700/20 border-yellow-700/40 text-yellow-300',
                            'slate'  => 'bg-slate-700/20 border-slate-700/40 text-slate-300',
                            'green'  => 'bg-green-700/20 border-green-700/40 text-green-300',
                            'red'    => 'bg-red-700/20 border-red-700/40 text-red-300',
                            'gray'   => 'bg-gray-700/20 border-gray-700/40 text-gray-300',
                        ];
                    @endphp
                    @foreach($eventos as $evento)
                        <li wire:key="evt-{{ $loop->index }}-{{ $evento['fecha']?->timestamp }}"
                            class="flex gap-4">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <span class="w-9 h-9 rounded-full flex items-center justify-center border {{ $colorClases[$evento['color']] ?? $colorClases['gray'] }}">
                                    <span class="material-icons text-base">{{ $evento['icono'] }}</span>
                                </span>
                            </div>

                            <div class="flex-1 bg-cerberus-dark border border-cerberus-steel rounded-lg px-4 py-3">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <p class="text-cerberus-light font-medium text-sm">
                                        {{ $evento['titulo'] }}
                                    </p>

                                    <div class="flex items-center gap-2">
                                        @if($evento['estado'])
                                            <span @class([
                                                'px-2 py-0.5 rounded-full text-xs',
                                                'bg-green-700/30 text-green-300 border border-green-700/40' => $evento['estado'] === 'Vigente',
                                                'bg-cerberus-steel/30 text-cerberus-light border border-cerberus-steel/40' => $evento['estado'] !== 'Vigente',
                                            ])>
                                                {{ $evento['estado'] }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-cerberus-steel whitespace-nowrap">
                                            {{ $evento['fecha']?->format('d/m/Y H:i') ?? '—' }}
                                        </span>
                                    </div>
                                </div>

                                @if($evento['detalle'])
                                    <p class="text-xs text-cerberus-light mt-1">
                                        {{ $evento['detalle'] }}
                                    </p>
                                @endif

                                <div class="flex items-center gap-1 mt-2 text-xs text-cerberus-steel">
                                    <span class="material-icons text-xs">person</span>
                                    {{ $evento['usuario'] }}
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Paginación --}}
            <div class="px-5 py-4 border-t border-cerberus-steel">
                {{ $eventos->links() }}
            </div>
        @endif

    </div>

</div>
