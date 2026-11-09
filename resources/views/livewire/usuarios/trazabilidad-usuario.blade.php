<div class="space-y-6">

    {{-- HEADER DEL USUARIO --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">timeline</span>
                <div>
                    <h2 class="text-xl font-bold text-cerberus-light">
                        Trazabilidad completa
                    </h2>
                    <p class="text-cerberus-light text-sm mt-0.5">
                        {{ $usuario->name }}
                        · <span class="text-cerberus-accent">{{ $usuario->cargo->nombre ?? '—' }}</span>
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.usuarios.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-cerberus-dark border border-cerberus-steel
                      text-cerberus-light hover:text-cerberus-accent rounded-lg text-sm transition">
                <span class="material-icons text-sm">arrow_back</span>
                Volver al listado
            </a>
        </div>

        {{-- Datos clave del usuario --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5">
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Departamento</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $usuario->departamento->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Empresa</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $usuario->empresaNomina->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Ubicación</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $usuario->ubicacion->nombre ?? '—' }}</p>
            </div>
            <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                <p class="text-xs text-cerberus-light mb-1">Jefe</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $usuario->jefe->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- EQUIPOS ACTIVOS AHORA MISMO --}}
    @if($equiposAsignados->isNotEmpty() || $equiposPrestados->isNotEmpty())
        <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-5">
            <h3 class="text-cerberus-light font-semibold text-sm mb-4 flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-base">inventory_2</span>
                Equipos que tiene actualmente
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($equiposAsignados as $item)
                    @php
                        $meses   = $item->mesesConReceptor();
                        $umbral  = $item->mesesRotacionRecomendada();
                        $supera  = $item->superaRotacionRecomendada();
                    @endphp
                    <div @class([
                        'rounded-lg p-3 border',
                        'bg-red-700/10 border-red-700/40' => $supera,
                        'bg-cerberus-dark border-cerberus-steel' => ! $supera,
                    ])>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="bg-blue-700/20 text-blue-300 border border-blue-700/40 text-[10px] font-semibold rounded-full px-2 py-0.5">
                                Asignado
                            </span>
                            @if($supera)
                                <span class="flex items-center gap-1 text-red-300 text-[10px] font-semibold"
                                      title="Lleva más tiempo que el periodo de rotación recomendado para esta categoría ({{ $umbral }} meses). No indica que el equipo esté dañado — es solo una referencia de permanencia.">
                                    <span class="material-icons text-xs">autorenew</span>
                                    Evaluar rotación
                                </span>
                            @endif
                        </div>
                        <p class="text-cerberus-light font-medium text-sm truncate">
                            {{ $item->equipo->codigo_interno ?? $item->equipo->nombre ?? 'Equipo' }}
                        </p>
                        <p class="text-cerberus-steel text-xs">
                            {{ $item->equipo->categoria->nombre ?? '—' }}
                        </p>
                        @if($meses !== null)
                            <p class="text-xs mt-1 {{ $supera ? 'text-red-300' : 'text-cerberus-accent' }}">
                                Lleva {{ $meses }} {{ $meses === 1 ? 'mes' : 'meses' }} con este equipo
                                @if($umbral)
                                    <span class="text-cerberus-steel">(recomendado: {{ $umbral }}m)</span>
                                @endif
                            </p>
                        @endif
                    </div>
                @endforeach

                @foreach($equiposPrestados as $item)
                    <div class="bg-cerberus-dark rounded-lg p-3 border border-cerberus-steel">
                        <span class="bg-amber-700/20 text-amber-300 border border-amber-700/40 text-[10px] font-semibold rounded-full px-2 py-0.5">
                            Préstamo
                        </span>
                        <p class="text-cerberus-light font-medium text-sm truncate mt-1">
                            {{ $item->equipo->codigo_interno ?? $item->equipo->nombre ?? 'Equipo' }}
                        </p>
                        <p class="text-cerberus-steel text-xs">
                            {{ $item->equipo->categoria->nombre ?? '—' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- FILTROS --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">
        <div class="flex flex-wrap gap-4 items-start">

            {{-- Filtro por tipo de evento --}}
            <div class="min-w-[160px]">
                <x-form.select label="Tipo de evento" placeholder="Todos" wire:model.live="tipo" :options="[
                    'asignacion' => 'Asignación',
                    'prestamo'   => 'Préstamo',
                    'datos'      => 'Datos del usuario',
                ]" />
            </div>

            {{-- Fecha desde --}}
            <div>
                <x-form.input label="Desde" type="date" wire:model.live="fecha_desde" />
            </div>

            {{-- Fecha hasta --}}
            <div>
                <x-form.input label="Hasta" type="date" wire:model.live="fecha_hasta" />
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
                <p class="text-sm">No hay eventos registrados para este usuario.</p>
            </div>
        @else
            <div class="p-5">
                <ul class="space-y-4">
                    @php
                        $colorClases = [
                            'blue'   => 'bg-blue-700/20 border-blue-700/40 text-blue-300',
                            'sky'    => 'bg-sky-700/20 border-sky-700/40 text-sky-300',
                            'amber'  => 'bg-amber-700/20 border-amber-700/40 text-amber-300',
                            'yellow' => 'bg-yellow-700/20 border-yellow-700/40 text-yellow-300',
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
                                    <span class="text-xs text-cerberus-steel whitespace-nowrap">
                                        {{ $evento['fecha']?->format('d/m/Y H:i') ?? '—' }}
                                    </span>
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
