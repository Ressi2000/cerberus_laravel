<div>
    @if($open && $equipo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            {{-- Modal --}}
            <div class="relative z-50 w-full max-w-3xl mx-4 bg-cerberus-mid border border-cerberus-steel
                        rounded-xl shadow-cerberus max-h-[90vh] flex flex-col">

                {{-- Header --}}
                <div class="flex justify-between items-center px-6 py-4 border-b border-cerberus-steel flex-shrink-0">
                    <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">devices</span>
                        Detalle del Equipo
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- Contenido scrollable --}}
                <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">

                    {{-- Fila superior: código + badges --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="font-mono text-lg font-bold text-white">
                            {{ $equipo->codigo_interno }}
                        </span>

                        <span class="px-2 py-0.5 text-xs rounded-full bg-cerberus-primary/20
                                     border border-cerberus-primary/40 text-cerberus-accent">
                            {{ $equipo->categoria->nombre }}
                        </span>

                        <span @class([
                            'px-2 py-0.5 text-xs rounded-full font-medium',
                            'bg-green-700/30 text-green-300 border border-green-700/40'
                                => $equipo->estado->nombre === 'Disponible',
                            'bg-blue-700/30 text-blue-300 border border-blue-700/40'
                                => $equipo->estado->nombre === 'Asignado',
                            'bg-yellow-700/30 text-yellow-300 border border-yellow-700/40'
                                => str_contains($equipo->estado->nombre, 'préstamo') || str_contains($equipo->estado->nombre, 'Prestamo'),
                            'bg-orange-700/30 text-orange-300 border border-orange-700/40'
                                => str_contains($equipo->estado->nombre, 'reparación') || str_contains($equipo->estado->nombre, 'Mantenimiento'),
                            'bg-red-700/30 text-red-300 border border-red-700/40'
                                => $equipo->estado->nombre === 'Baja',
                            'bg-cerberus-steel/30 text-cerberus-light border border-cerberus-steel/40'
                                => true, // fallback
                        ])>
                            {{ $equipo->estado->nombre }}
                        </span>
                    </div>

                    {{-- Datos base --}}
                    <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                        <h4 class="text-cerberus-accent font-semibold text-sm mb-3">Datos generales</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
                            <p>
                                <span class="font-semibold text-white">Serial:</span>
                                {{ $equipo->serial ?? '—' }}
                            </p>
                            <p>
                                <span class="font-semibold text-white">Hostname:</span>
                                {{ $equipo->nombre_maquina ?? '—' }}
                            </p>
                            <p>
                                <span class="font-semibold text-white">Empresa:</span>
                                {{ $equipo->empresa->nombre ?? '—' }}
                            </p>
                            <p>
                                <span class="font-semibold text-white">Ubicación:</span>
                                {{ $equipo->ubicacion->nombre ?? '—' }}
                            </p>
                            <p>
                                <span class="font-semibold text-white">Adquisición:</span>
                                {{ $equipo->fecha_adquisicion
                                    ? \Carbon\Carbon::parse($equipo->fecha_adquisicion)->format('d/m/Y')
                                    : '—' }}
                            </p>
                            <p>
                                <span class="font-semibold text-white">Garantía hasta:</span>
                                @if($equipo->fecha_garantia_fin)
                                    @php
                                        $garantia = \Carbon\Carbon::parse($equipo->fecha_garantia_fin);
                                        $vencida  = $garantia->isPast();
                                    @endphp
                                    <span @class(['text-red-400' => $vencida, 'text-green-400' => !$vencida])>
                                        {{ $garantia->format('d/m/Y') }}
                                        {{ $vencida ? '(vencida)' : '' }}
                                    </span>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        @if($equipo->observaciones)
                            <p class="mt-3 text-sm text-cerberus-light">
                                <span class="font-semibold text-white">Observaciones:</span>
                                {{ $equipo->observaciones }}
                            </p>
                        @endif
                    </div>

                    {{-- Características técnicas actuales --}}
                    @if($equipo->atributosActuales->count())
                        <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                            <h4 class="text-cerberus-accent font-semibold text-sm mb-3">
                                Características técnicas
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
                                @foreach($equipo->atributosActuales as $val)
                                    <p>
                                        <span class="font-semibold text-white">
                                            {{ $val->atributo->nombre }}:
                                        </span>
                                        @if($val->atributo->tipo === 'boolean')
                                            {{ $val->valor ? 'Sí' : 'No' }}
                                        @else
                                            {{ $val->valor }}
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Historial de cambios EAV --}}
                    @if(count($historial))
                        <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                            <h4 class="text-cerberus-accent font-semibold text-sm mb-3 flex items-center gap-2">
                                <span class="material-icons text-base">history</span>
                                Historial de cambios
                            </h4>

                            <div class="space-y-3">
                                @foreach($historial as $nombreAtributo => $versiones)
                                    @if(count($versiones) > 1)
                                        {{-- Solo mostramos atributos que tienen más de 1 versión --}}
                                        <div>
                                            <p class="text-white text-xs font-semibold mb-1">
                                                {{ $nombreAtributo }}
                                            </p>
                                            <div class="space-y-1 pl-3 border-l border-cerberus-steel/50">
                                                @foreach($versiones as $version)
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span @class([
                                                            'w-1.5 h-1.5 rounded-full flex-shrink-0',
                                                            'bg-cerberus-accent' => $version['es_actual'],
                                                            'bg-cerberus-steel'  => !$version['es_actual'],
                                                        ])></span>
                                                        <span @class([
                                                            $version['es_actual']
                                                                ? 'text-white font-medium'
                                                                : 'text-cerberus-steel line-through',
                                                        ])>
                                                            {{ $version['valor'] }}
                                                        </span>
                                                        <span class="text-cerberus-steel ml-auto">
                                                            {{ $version['fecha'] }} — {{ $version['usuario'] }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Registro --}}
                    <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                        <h4 class="text-cerberus-accent font-semibold text-sm mb-2">Registro</h4>
                        <p class="text-sm text-cerberus-light">
                            <span class="font-semibold text-white">Creado:</span>
                            {{ $equipo->created_at?->format('d/m/Y H:i') }}
                        </p>
                        @if($equipo->updated_at != $equipo->created_at)
                            <p class="text-sm text-cerberus-light mt-1">
                                <span class="font-semibold text-white">Última modificación:</span>
                                {{ $equipo->updated_at?->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex justify-between items-center px-6 py-4
                            border-t border-cerberus-steel flex-shrink-0">
                    <a href="{{ route('admin.equipos.edit', $equipo) }}"
                        class="px-4 py-2 text-sm bg-cerberus-primary hover:bg-cerberus-hover
                               text-white rounded-lg transition flex items-center gap-1">
                        <span class="material-icons text-sm">edit</span>
                        Editar equipo
                    </a>
                    <button wire:click="close"
                        class="px-5 py-2 text-sm bg-cerberus-steel/30 hover:bg-cerberus-steel/50
                               text-white rounded-lg transition">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>