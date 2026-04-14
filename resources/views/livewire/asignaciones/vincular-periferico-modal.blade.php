{{--
    resources/views/livewire/asignaciones/vincular-periferico-modal.blade.php

    Modal para vincular un periférico (AsignacionItem) a un equipo principal
    activo del mismo receptor, incluso si vienen de asignaciones distintas.

    Eventos escuchados : openVincularPeriferico (con id del item)
    Eventos emitidos   : perifericoVinculado (para refrescar la tabla padre)
--}}

<div>
    @if ($open && $item)
        {{-- ── BACKDROP ────────────────────────────────────────────────────── --}}
        <div class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"
             wire:click="cerrar">
        </div>

        {{-- ── PANEL MODAL ─────────────────────────────────────────────────── --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-cerberus-mid border border-cerberus-steel
                        rounded-2xl shadow-cerberus overflow-hidden"
                 @click.stop>

                {{-- ── CABECERA ──────────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-6 py-4
                            border-b border-cerberus-steel">
                    <div class="flex items-center gap-3">
                        <span class="material-icons text-cerberus-accent text-2xl">
                            cable
                        </span>
                        <div>
                            <h2 class="text-white font-semibold text-base leading-tight">
                                Vincular periférico a equipo principal
                            </h2>
                            <p class="text-cerberus-accent text-xs mt-0.5">
                                Asignación #{{ $item->asignacion_id }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="cerrar"
                            class="text-cerberus-accent hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- ── CUERPO ────────────────────────────────────────────── --}}
                <div class="px-6 py-5 space-y-5">

                    {{-- Info del periférico que se está vinculando --}}
                    <div class="bg-cerberus-dark border border-cerberus-steel
                                rounded-xl p-4 space-y-1">
                        <p class="text-cerberus-accent text-xs uppercase tracking-wide mb-2">
                            Periférico a vincular
                        </p>
                        <div class="flex items-center gap-3">
                            <span class="material-icons text-cerberus-primary text-2xl">
                                memory
                            </span>
                            <div>
                                <p class="text-white font-medium text-sm">
                                    {{ $item->equipo->categoria->nombre ?? '—' }}
                                    —
                                    {{ $item->equipo->codigo_interno }}
                                </p>
                                @if($item->equipo->serial)
                                    <p class="text-cerberus-accent text-xs">
                                        S/N: {{ $item->equipo->serial }}
                                    </p>
                                @endif
                                <p class="text-cerberus-light text-xs mt-0.5">
                                    Receptor: {{ $item->asignacion->receptorNombre() }}
                                </p>
                            </div>
                        </div>

                        {{-- Si ya tiene un padre, lo mostramos --}}
                        @if ($item->padre)
                            <div class="mt-3 pt-3 border-t border-cerberus-steel
                                        flex items-center gap-2">
                                <span class="material-icons text-yellow-400 text-base">
                                    link
                                </span>
                                <p class="text-yellow-300 text-xs">
                                    Actualmente vinculado a:
                                    <span class="font-semibold text-white">
                                        {{ $item->padre->equipo->categoria->nombre ?? '—' }}
                                        — {{ $item->padre->equipo->codigo_interno }}
                                    </span>
                                    (Asig. #{{ $item->padre->asignacion_id }})
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Selector de equipo principal --}}
                    @if ($principalesDisponibles->isEmpty())
                        <div class="bg-yellow-500/10 border border-yellow-500/30
                                    rounded-xl p-4 flex items-start gap-3">
                            <span class="material-icons text-yellow-400 text-xl mt-0.5">
                                warning
                            </span>
                            <div>
                                <p class="text-yellow-300 text-sm font-medium">
                                    No hay equipos principales disponibles
                                </p>
                                <p class="text-yellow-200/70 text-xs mt-1">
                                    El receptor no tiene ningún equipo principal activo
                                    en sus asignaciones. Puedes desvincular el periférico
                                    para dejarlo como equipo independiente.
                                </p>
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="block text-cerberus-accent text-sm mb-2">
                                Seleccionar equipo principal
                                <span class="text-red-400">*</span>
                            </label>

                            <div class="space-y-2 max-h-52 overflow-y-auto pr-1
                                        scrollbar-thin scrollbar-track-cerberus-dark
                                        scrollbar-thumb-cerberus-steel">
                                @foreach ($principalesDisponibles as $principal)
                                    <label
                                        class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer
                                               transition-all
                                               {{ $padreId == $principal->id
                                                    ? 'bg-cerberus-primary/20 border-cerberus-primary'
                                                    : 'bg-cerberus-dark border-cerberus-steel hover:border-cerberus-accent' }}">

                                        <input type="radio"
                                               wire:model="padreId"
                                               value="{{ $principal->id }}"
                                               class="text-cerberus-primary focus:ring-cerberus-primary">

                                        <div class="flex-1 min-w-0">
                                            <p class="text-white text-sm font-medium truncate">
                                                {{ $principal->equipo->categoria->nombre ?? '—' }}
                                                — {{ $principal->equipo->codigo_interno }}
                                            </p>
                                            <div class="flex items-center gap-3 mt-0.5">
                                                @if($principal->equipo->serial)
                                                    <span class="text-cerberus-accent text-xs">
                                                        S/N: {{ $principal->equipo->serial }}
                                                    </span>
                                                @endif
                                                @if($principal->equipo->nombre_maquina)
                                                    <span class="text-cerberus-accent text-xs">
                                                        {{ $principal->equipo->nombre_maquina }}
                                                    </span>
                                                @endif
                                                <span class="text-cerberus-light text-xs">
                                                    Asig. #{{ $principal->asignacion_id }}
                                                </span>
                                            </div>
                                            {{-- Periféricos ya vinculados a este principal --}}
                                            @if ($principal->hijosActivos->count() > 0)
                                                <p class="text-cerberus-accent text-xs mt-1">
                                                    <span class="material-icons text-xs align-middle">
                                                        device_hub
                                                    </span>
                                                    {{ $principal->hijosActivos->count() }}
                                                    periférico(s) vinculado(s)
                                                </p>
                                            @endif
                                        </div>

                                        {{-- Badge: indica si es de una asignación diferente --}}
                                        @if ($principal->asignacion_id !== $item->asignacion_id)
                                            <span class="shrink-0 px-2 py-0.5 text-xs rounded-full
                                                         bg-cerberus-steel/50 text-cerberus-light">
                                                Otra asig.
                                            </span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>

                            @error('padreId')
                                <p class="text-red-400 text-xs mt-2 flex items-center gap-1">
                                    <span class="material-icons text-xs">error</span>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif

                </div>

                {{-- ── PIE: ACCIONES ─────────────────────────────────────── --}}
                <div class="px-6 py-4 border-t border-cerberus-steel
                            flex items-center justify-between gap-3">

                    {{-- Desvincular (solo si tiene padre actualmente) --}}
                    <div>
                        @if ($item->padre)
                            <button wire:click="desvincular"
                                    wire:confirm="¿Desvincular este periférico de su equipo actual? Quedará como equipo independiente."
                                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg
                                           text-sm text-yellow-300 border border-yellow-500/40
                                           hover:bg-yellow-500/10 transition">
                                <span class="material-icons text-base">link_off</span>
                                Quitar vínculo
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- Cancelar --}}
                        <button wire:click="cerrar"
                                class="px-4 py-2 rounded-lg text-sm text-cerberus-light
                                       border border-cerberus-steel hover:bg-cerberus-dark transition">
                            Cancelar
                        </button>

                        {{-- Confirmar --}}
                        @unless ($principalesDisponibles->isEmpty())
                            <button wire:click="confirmar"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmar"
                                    class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm
                                           font-semibold text-white bg-cerberus-primary
                                           hover:bg-cerberus-hover transition
                                           disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="confirmar"
                                      class="material-icons text-base">
                                    link
                                </span>
                                <span wire:loading wire:target="confirmar"
                                      class="material-icons text-base animate-spin">
                                    progress_activity
                                </span>
                                <span wire:loading.remove wire:target="confirmar">
                                    Vincular
                                </span>
                                <span wire:loading wire:target="confirmar">
                                    Vinculando...
                                </span>
                            </button>
                        @endunless
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>