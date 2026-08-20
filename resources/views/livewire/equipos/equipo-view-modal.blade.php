<div>
    @if ($open && $equipo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            {{-- Modal --}}
            <div
                class="relative z-50 w-full max-w-3xl mx-4 bg-cerberus-mid border border-cerberus-steel
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

                        <span
                            class="px-2 py-0.5 text-xs rounded-full bg-cerberus-primary/20
                                     border border-cerberus-primary/40 text-cerberus-accent">
                            {{ $equipo->categoria->nombre }}
                        </span>

                        <x-ui.estado-badge :estado="$equipo->estado" />
                    </div>

                    {{-- ── ASIGNACIÓN ACTIVA ─────────────────────────────────────────────── --}}
                    @if ($asignacionActiva)
                        <div class="bg-blue-900/20 border border-blue-700/40 rounded-xl px-4 py-3 flex flex-col gap-2">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-icons text-blue-400 text-base">assignment_ind</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-blue-300">
                                    Equipo asignado actualmente
                                </span>
                            </div>

                            @if ($asignacionActiva->asignacion->usuario_id)
                                {{-- Asignación personal --}}
                                @php $usuario = $asignacionActiva->asignacion->usuario @endphp
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Asignado a</span>
                                        <p class="text-white font-medium">{{ $usuario?->name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Cargo</span>
                                        <p class="text-white">{{ $usuario?->cargo?->nombre ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Empresa</span>
                                        <p class="text-white">{{ $usuario?->empresaNomina?->nombre ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Ubicación</span>
                                        <p class="text-white">{{ $usuario?->ubicacion?->nombre ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Departamento</span>
                                        <p class="text-white">{{ $usuario?->departamento?->nombre ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Ubicación de la Asignación</span>
                                        <p class="text-white">
                                            {{ $asignacionActiva?->asignacion?->empresa?->nombre ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Fecha asignación</span>
                                        <p class="text-white">
                                            {{ $asignacionActiva->asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                {{-- Asignación a área --}}
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Asignado a área</span>
                                        <p class="text-white font-medium">
                                            {{ $asignacionActiva->asignacion->areaDepartamento?->nombre ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Empresa del área</span>
                                        <p class="text-white">
                                            {{ $asignacionActiva->asignacion->areaEmpresa?->nombre ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Responsable</span>
                                        <p class="text-white">
                                            {{ $asignacionActiva->asignacion->areaResponsable?->name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-cerberus-steel text-xs">Fecha asignación</span>
                                        <p class="text-white">
                                            {{ $asignacionActiva->asignacion->fecha_asignacion?->format('d/m/Y') ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <div class="text-xs text-cerberus-steel mt-1">
                                Analista: <span
                                    class="text-white">{{ $asignacionActiva->asignacion->analista?->name ?? '—' }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Datos base --}}
                    <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                        <h4 class="text-cerberus-accent font-semibold text-sm mb-3">Datos generales</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
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
                                {{ $equipo->fecha_adquisicion ? \Carbon\Carbon::parse($equipo->fecha_adquisicion)->format('d/m/Y') : '—' }}
                            </p>
                            <p>
                                <span class="font-semibold text-white">Garantía hasta:</span>
                                @if ($equipo->fecha_garantia_fin)
                                    @php
                                        $garantia = \Carbon\Carbon::parse($equipo->fecha_garantia_fin);
                                        $vencida = $garantia->isPast();
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
                        @if ($equipo->observaciones)
                            <p class="mt-3 text-sm text-cerberus-light">
                                <span class="font-semibold text-white">Observaciones:</span>
                                {{ $equipo->observaciones }}
                            </p>
                        @endif
                    </div>

                    {{-- Características técnicas actuales --}}
                    @if ($equipo->atributosActuales->count())
                        <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                            <h4 class="text-cerberus-accent font-semibold text-sm mb-3">
                                Características técnicas
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
                                @foreach ($equipo->atributosActuales as $val)
                                    <p>
                                        <span class="font-semibold text-white">
                                            {{ $val->atributo->nombre }}:
                                        </span>
                                        @if ($val->atributo->tipo === 'boolean')
                                            {{ $val->valor ? 'Sí' : 'No' }}
                                        @else
                                            {{ $val->valor }}
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Características técnicas agrupadas (atributos tipo 'group') --}}
                    @if ($equipo->grupoInstancias->isNotEmpty())
                        @foreach ($equipo->grupoInstancias->groupBy('atributo_id') as $instancias)
                            @php $atributoGrupo = $instancias->first()->atributo @endphp
                            <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                                <h4 class="text-cerberus-accent font-semibold text-sm mb-3 flex items-center gap-2">
                                    {{ $atributoGrupo?->nombre ?? 'Grupo eliminado' }}
                                    <span class="text-xs text-cerberus-steel font-normal">
                                        ({{ $instancias->count() }})
                                    </span>
                                </h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left whitespace-nowrap">
                                        <thead>
                                            <tr class="border-b border-cerberus-steel/50 text-xs text-cerberus-steel uppercase tracking-wide">
                                                @foreach ($atributoGrupo?->sub_campos ?? [] as $sub)
                                                    <th class="py-1.5 pr-4 font-medium">{{ $sub['nombre'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-cerberus-steel/20">
                                            @foreach ($instancias->values() as $instancia)
                                                <tr>
                                                    @foreach ($atributoGrupo?->sub_campos ?? [] as $sub)
                                                        @php $valor = $instancia->valores[$sub['id']] ?? null @endphp
                                                        <td class="py-1.5 pr-4 text-cerberus-light">
                                                            @if ($valor === true || $valor === '1' || $valor === 1)
                                                                Sí
                                                            @elseif ($valor === false || $valor === '0' || $valor === 0)
                                                                No
                                                            @else
                                                                {{ $valor ?? '—' }}
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- Registro --}}
                    <div class="bg-cerberus-dark border border-cerberus-steel rounded-xl p-4">
                        <h4 class="text-cerberus-accent font-semibold text-sm mb-2">Registro</h4>
                        <p class="text-sm text-cerberus-light">
                            <span class="font-semibold text-white">Creado:</span>
                            {{ $equipo->created_at?->format('d/m/Y H:i') }}
                        </p>
                        @if ($equipo->updated_at != $equipo->created_at)
                            <p class="text-sm text-cerberus-light mt-1">
                                <span class="font-semibold text-white">Última modificación:</span>
                                {{ $equipo->updated_at?->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>

                </div>

                {{-- Footer --}}
                <div
                    class="flex justify-between items-center px-6 py-4
                            border-t border-cerberus-steel flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.equipos.edit', $equipo) }}"
                            class="px-4 py-2 text-sm bg-cerberus-primary hover:bg-cerberus-hover
                                   text-white rounded-lg transition flex items-center gap-1">
                            <span class="material-icons text-sm">edit</span>
                            Editar equipo
                        </a>
                        <a href="{{ route('admin.equipos.show', $equipo) }}" wire:navigate
                            class="px-4 py-2 text-sm bg-cerberus-steel/30 hover:bg-cerberus-steel/50
                                   text-white rounded-lg transition flex items-center gap-1">
                            <span class="material-icons text-sm">history</span>
                            Ver historial
                        </a>
                    </div>
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
