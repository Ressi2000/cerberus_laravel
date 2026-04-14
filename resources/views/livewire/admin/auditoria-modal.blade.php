<div>

    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data
            x-on:keydown.escape.window="$wire.cerrar()">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrar"></div>

            {{-- Panel --}}
            <div
                class="relative z-10 w-full max-w-2xl max-h-[90vh] flex flex-col
                    bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-2xl overflow-hidden">

                {{-- ── Cabecera ─────────────────────────────────────────────────── --}}
                <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-cerberus-steel">
                    <div class="flex items-center gap-3">
                        <span class="material-icons text-cerberus-accent text-2xl">manage_search</span>
                        <div>
                            <h2 class="text-white font-bold text-lg leading-tight">Detalle de auditoría</h2>
                            <p class="text-cerberus-light text-xs mt-0.5">
                                Tabla:
                                <span class="text-cerberus-accent font-mono">{{ $logTabla }}</span>
                                · Registro #{{ $logRegistroId }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="cerrar" class="text-cerberus-light hover:text-white transition shrink-0 mt-0.5">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- ── Meta del evento ─────────────────────────────────────────── --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 px-6 py-4 border-b border-cerberus-steel/50 text-sm">

                    <div>
                        <p class="text-cerberus-light text-xs mb-0.5">Fecha</p>
                        <p class="text-white font-medium">{{ $logFecha }}</p>
                    </div>

                    <div>
                        <p class="text-cerberus-light text-xs mb-0.5">Usuario</p>
                        <p class="text-white font-medium">{{ $logUsuario }}</p>
                    </div>

                    <div>
                        <p class="text-cerberus-light text-xs mb-0.5">Acción</p>
                        <x-table.audit-action-badge :accion="$logAccion" />
                    </div>

                    <div>
                        <p class="text-cerberus-light text-xs mb-0.5">ID Registro</p>
                        <p class="text-white font-mono">#{{ $logRegistroId }}</p>
                    </div>

                </div>

                {{-- ── Contenido scrollable ──────────────────────────────────────── --}}
                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                    {{-- ── CREAR: muestra valores iniciales del registro ──────────── --}}
                    @if (!empty($valoresCreacion))
                        <div>
                            <h3 class="text-white text-sm font-semibold mb-3 flex items-center gap-2">
                                <span class="material-icons text-green-400 text-base">add_circle</span>
                                Registro creado con los siguientes valores
                            </h3>
                            <div
                                class="divide-y divide-cerberus-steel/40 rounded-xl border border-cerberus-steel overflow-hidden">
                                @foreach ($valoresCreacion as $fila)
                                    <div
                                        class="grid grid-cols-2 gap-2 px-4 py-2.5 hover:bg-cerberus-darkest/50 transition text-sm">
                                        <span class="text-cerberus-light">{{ $fila['etiqueta'] }}</span>
                                        <span class="text-white break-words">
                                            @if (!$fila['valor'] || $fila['valor'] === '—')
                                                <span class="text-cerberus-light/40 italic">—</span>
                                            @else
                                                {{ $fila['valor'] }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ── ELIMINAR: valores que tenía el registro ─────────────────── --}}
                    @if (!empty($valoresEliminacion))
                        <div>
                            <h3 class="text-white text-sm font-semibold mb-3 flex items-center gap-2">
                                <span class="material-icons text-red-400 text-base">delete</span>
                                Registro eliminado — valores que tenía
                            </h3>
                            <div
                                class="divide-y divide-cerberus-steel/40 rounded-xl border border-red-700/30 overflow-hidden">
                                @foreach ($valoresEliminacion as $fila)
                                    <div
                                        class="grid grid-cols-2 gap-2 px-4 py-2.5 hover:bg-cerberus-darkest/50 transition text-sm">
                                        <span class="text-cerberus-light">{{ $fila['etiqueta'] }}</span>
                                        <span class="text-red-300 break-words">
                                            @if (!$fila['valor'] || $fila['valor'] === '—')
                                                <span class="text-cerberus-light/40 italic">—</span>
                                            @else
                                                {{ $fila['valor'] }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ── ACTUALIZAR: tabla de diff antes/después ─────────────────── --}}
                    @if (!empty($cambiosResueltos))
                        <div>
                            <h3 class="text-white text-sm font-semibold mb-3 flex items-center gap-2">
                                <span class="material-icons text-yellow-400 text-base">compare_arrows</span>
                                {{ count($cambiosResueltos) }} campo(s) modificado(s)
                            </h3>

                            {{-- Encabezado --}}
                            <div
                                class="grid grid-cols-3 gap-2 px-4 py-2 text-xs font-semibold text-cerberus-light
                                    uppercase tracking-wide border-b border-cerberus-steel
                                    bg-cerberus-darkest/60 rounded-t-xl">
                                <span>Campo</span>
                                <span>Antes</span>
                                <span>Después</span>
                            </div>

                            <div
                                class="divide-y divide-cerberus-steel/40 border border-t-0
                                    border-cerberus-steel rounded-b-xl overflow-hidden">
                                @foreach ($cambiosResueltos as $cambio)
                                    <div
                                        class="grid grid-cols-3 gap-2 px-4 py-3
                                            hover:bg-cerberus-darkest/50 transition text-sm items-start">

                                        <span class="text-cerberus-light font-medium">
                                            {{ $cambio['etiqueta'] }}
                                        </span>

                                        <span class="text-red-300 break-words">
                                            @if (!$cambio['antes'] || $cambio['antes'] === '—')
                                                <span class="text-cerberus-light/40 italic">—</span>
                                            @else
                                                <span class="inline-flex items-start gap-1">
                                                    <span
                                                        class="material-icons text-red-400 text-xs mt-0.5 shrink-0">remove_circle_outline</span>
                                                    {{ $cambio['antes'] }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="text-green-300 break-words">
                                            @if (!$cambio['despues'] || $cambio['despues'] === '—')
                                                <span class="text-cerberus-light/40 italic">—</span>
                                            @else
                                                <span class="inline-flex items-start gap-1">
                                                    <span
                                                        class="material-icons text-green-400 text-xs mt-0.5 shrink-0">add_circle_outline</span>
                                                    {{ $cambio['despues'] }}
                                                </span>
                                            @endif
                                        </span>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Sin datos --}}
                    @if (empty($cambiosResueltos) && empty($valoresCreacion) && empty($valoresEliminacion))
                        <div class="text-center py-10">
                            <span class="material-icons text-cerberus-steel text-4xl mb-2 block">info</span>
                            <p class="text-cerberus-light text-sm">
                                No hay detalle de cambios registrado para esta acción.
                            </p>
                        </div>
                    @endif

                </div>

                {{-- ── Footer ──────────────────────────────────────────────────────── --}}
                <div class="px-6 py-4 border-t border-cerberus-steel flex justify-end">
                    <button wire:click="cerrar"
                        class="px-4 py-2 rounded-lg bg-cerberus-dark border border-cerberus-steel
                           text-cerberus-light hover:text-white hover:border-cerberus-primary
                           transition text-sm">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
