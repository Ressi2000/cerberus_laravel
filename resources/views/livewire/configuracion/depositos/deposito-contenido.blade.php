<div class="space-y-6">

    {{-- HEADER --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="material-icons text-cerberus-accent text-2xl">warehouse</span>
                <div>
                    <h2 class="text-xl font-bold text-cerberus-light">{{ $deposito->nombre }}</h2>
                    <p class="text-cerberus-light text-sm mt-0.5">
                        {{ $deposito->empresa->nombre ?? '—' }}
                        @if ($deposito->descripcion)
                            · {{ $deposito->descripcion }}
                        @endif
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.configuracion.depositos') }}"
               class="flex items-center gap-2 px-4 py-2 bg-cerberus-dark border border-cerberus-steel
                      text-cerberus-light hover:text-cerberus-accent rounded-lg text-sm transition">
                <span class="material-icons text-sm">arrow_back</span>
                Volver a Depósitos
            </a>
        </div>
    </div>

    {{-- TRASLADAR (modal inline) --}}
    @if ($trasladarAbierto)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrarTrasladar"></div>
            <div class="relative z-50 w-full max-w-md mx-4 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus p-6">
                <h3 class="text-lg font-semibold text-cerberus-light mb-4 flex items-center gap-2">
                    <span class="material-icons text-cerberus-accent">local_shipping</span>
                    Trasladar a otro depósito
                </h3>

                <x-form.select
                    label="Depósito destino"
                    placeholder="Selecciona..."
                    :options="$this->depositosDestino->pluck('nombre', 'id')"
                    wire:model="destinoId"
                    :error="$errors->first('destinoId')"
                />

                <div class="flex justify-end gap-3 mt-2">
                    <button wire:click="cerrarTrasladar"
                        class="px-4 py-2 text-sm rounded-lg bg-cerberus-steel/30 hover:bg-cerberus-steel/50 text-white transition">
                        Cancelar
                    </button>
                    <button wire:click="trasladar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition disabled:opacity-60">
                        Trasladar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- EQUIPOS ARCHIVADOS --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <h3 class="text-sm font-semibold text-cerberus-light mb-4 flex items-center gap-2">
            <span class="material-icons text-cerberus-accent text-base">devices</span>
            Equipos archivados
            <span class="text-xs font-normal text-cerberus-steel">({{ $this->equipos->count() }})</span>
        </h3>

        @forelse ($this->equipos as $equipo)
            <div wire:key="dep-equipo-{{ $equipo->id }}" class="flex items-center justify-between gap-3 py-2.5 {{ ! $loop->last ? 'border-b border-cerberus-steel/30' : '' }}">
                <div class="min-w-0">
                    <a href="{{ route('admin.equipos.show', $equipo) }}" wire:navigate class="text-sm text-cerberus-accent hover:underline">
                        {{ $equipo->codigo_interno }}
                    </a>
                    <p class="text-xs text-cerberus-steel">{{ $equipo->categoria->nombre ?? '—' }}</p>
                </div>
                <button wire:click="abrirTrasladarEquipo({{ $equipo->id }})"
                    class="flex-shrink-0 text-xs text-cerberus-accent hover:underline flex items-center gap-1">
                    <span class="material-icons text-sm">local_shipping</span> Trasladar
                </button>
            </div>
        @empty
            <p class="text-sm text-cerberus-steel text-center py-4">No hay equipos archivados en este depósito.</p>
        @endforelse
    </div>

    {{-- PIEZAS DESCARTADAS --}}
    <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-6">
        <h3 class="text-sm font-semibold text-cerberus-light mb-4 flex items-center gap-2">
            <span class="material-icons text-cerberus-accent text-base">memory</span>
            Piezas descartadas
            <span class="text-xs font-normal text-cerberus-steel">({{ $this->piezas->count() }})</span>
        </h3>

        @forelse ($this->piezas as $pieza)
            <div wire:key="dep-pieza-{{ $pieza->id }}" class="flex items-center justify-between gap-3 py-2.5 {{ ! $loop->last ? 'border-b border-cerberus-steel/30' : '' }}">
                <div class="min-w-0">
                    <p class="text-sm text-cerberus-light">{{ $pieza->atributo?->describirValor($pieza->valor_extraido) ?? 'Pieza' }}</p>
                    <p class="text-xs text-cerberus-steel">
                        De
                        @if ($pieza->equipoOrigen)
                            <a href="{{ route('admin.equipos.show', $pieza->equipoOrigen) }}" wire:navigate class="text-cerberus-accent hover:underline">
                                {{ $pieza->equipoOrigen->codigo_interno }}
                            </a>
                        @else
                            un equipo eliminado
                        @endif
                    </p>
                </div>
                <button wire:click="abrirTrasladarPieza({{ $pieza->id }})"
                    class="flex-shrink-0 text-xs text-cerberus-accent hover:underline flex items-center gap-1">
                    <span class="material-icons text-sm">local_shipping</span> Trasladar
                </button>
            </div>
        @empty
            <p class="text-sm text-cerberus-steel text-center py-4">No hay piezas descartadas en este depósito.</p>
        @endforelse
    </div>

</div>
