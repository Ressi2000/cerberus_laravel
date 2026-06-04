<div class="space-y-6">

    {{-- HEADER DEL USUARIO --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">history</span>
                <div>
                    <h2 class="text-xl font-bold text-cerberus-light">
                        Historial de cambios
                    </h2>
                    <p class="text-cerberus-light text-sm mt-0.5">
                        {{ $usuario->name }}
                        · <span class="font-mono text-cerberus-accent">{{ $usuario->username }}</span>
                        · {{ $usuario->getRoleNames()->first() ?? '—' }}
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
                <p class="text-xs text-cerberus-light mb-1">Estado</p>
                <p class="font-semibold text-sm
                    {{ $usuario->estado === 'Activo' ? 'text-green-400' : 'text-red-400' }}">
                    {{ $usuario->estado }}
                </p>
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
                <p class="text-xs text-cerberus-light mb-1">Cargo</p>
                <p class="text-cerberus-light font-semibold text-sm">{{ $usuario->cargo->nombre ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">
        <div class="flex flex-wrap gap-4 items-end">

            {{-- Filtro por acción --}}
            <div>
                <label class="block text-cerberus-accent text-xs mb-1">Acción</label>
                <select wire:model.live="accion"
                    class="bg-cerberus-dark border border-cerberus-steel text-cerberus-light text-sm rounded-lg px-3 py-2
                           focus:ring-2 focus:ring-cerberus-primary outline-none transition min-w-[160px]">
                    <option value="">Todas las acciones</option>
                    <option value="CREAR">Crear</option>
                    <option value="EDITAR">Editar</option>
                    <option value="INACTIVAR">Inactivar</option>
                    <option value="REACTIVAR">Reactivar</option>
                    <option value="ELIMINAR">Eliminar</option>
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
                <p class="text-sm">No hay registros de cambios para este usuario.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-cerberus-light">
                    <thead class="text-xs uppercase text-cerberus-accent bg-cerberus-darkest border-b border-cerberus-steel">
                        <tr>
                            <th class="px-5 py-3">Fecha y hora</th>
                            <th class="px-5 py-3">Acción</th>
                            <th class="px-5 py-3">Campos modificados</th>
                            <th class="px-5 py-3">Realizado por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cerberus-steel">
                        @foreach($historial as $registro)
                            <tr wire:key="hist-{{ $registro->id }}"
                                class="hover:bg-cerberus-darkest transition align-top">

                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="text-cerberus-light">
                                        {{ \Carbon\Carbon::parse($registro->created_at)->format('d/m/Y') }}
                                    </span>
                                    <span class="text-cerberus-light text-xs block">
                                        {{ \Carbon\Carbon::parse($registro->created_at)->format('H:i:s') }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 whitespace-nowrap">
                                    @php
                                        $accionConfig = match($registro->accion) {
                                            'CREAR'      => ['bg-green-700/40 text-green-300 border-green-700',  'add_circle',     'Crear'],
                                            'EDITAR'     => ['bg-blue-700/40 text-blue-300 border-blue-700',     'edit',           'Editar'],
                                            'INACTIVAR'  => ['bg-red-700/40 text-red-300 border-red-700',        'block',          'Inactivar'],
                                            'REACTIVAR'  => ['bg-emerald-700/40 text-emerald-300 border-emerald-700', 'check_circle', 'Reactivar'],
                                            'ELIMINAR'   => ['bg-red-900/40 text-red-200 border-red-900',        'delete',         'Eliminar'],
                                            default      => ['bg-cerberus-dark text-cerberus-light border-cerberus-steel', 'info', $registro->accion],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs border {{ $accionConfig[0] }}">
                                        <span class="material-icons text-xs">{{ $accionConfig[1] }}</span>
                                        {{ $accionConfig[2] }}
                                    </span>
                                </td>

                                <td class="px-5 py-3">
                                    @php $cambios = $registro->cambios @endphp
                                    @if(empty($cambios))
                                        <span class="text-cerberus-steel text-xs italic">Sin cambios de campo</span>
                                    @else
                                        <ul class="space-y-1">
                                            @foreach($cambios as $campo => $vals)
                                                <li class="text-xs">
                                                    <span class="font-semibold text-cerberus-accent">{{ $campo }}:</span>
                                                    <span class="line-through text-cerberus-steel">{{ $vals['before'] ?? '—' }}</span>
                                                    <span class="material-icons text-xs align-middle text-cerberus-steel">arrow_forward</span>
                                                    <span class="text-cerberus-light">{{ $vals['after'] ?? '—' }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>

                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons text-sm text-cerberus-steel">person</span>
                                        <span class="text-cerberus-light">{{ $registro->usuario?->name ?? 'Sistema' }}</span>
                                    </div>
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
