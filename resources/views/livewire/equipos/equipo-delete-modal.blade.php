<div>
    @if($open && $equipo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            {{-- Modal --}}
            <div class="relative z-50 w-full max-w-md mx-4 p-6 bg-cerberus-mid border border-cerberus-steel
                        rounded-xl shadow-cerberus">

                <h2 class="text-xl font-semibold text-white mb-2 flex items-center gap-2">
                    <span class="material-icons text-red-400">do_not_disturb_on</span>
                    Dar de baja equipo
                </h2>

                <p class="text-cerberus-light text-sm mb-1">
                    ¿Seguro que deseas dar de baja el equipo
                    <strong class="text-white font-mono">{{ $equipo->codigo_interno }}</strong>?
                </p>

                <p class="text-cerberus-steel text-xs mb-5">
                    El equipo quedará marcado como
                    <strong class="text-red-400">Dado de baja</strong>
                    y no podrá ser asignado ni editado.
                    El registro permanece para auditoría e historial.
                </p>

                {{-- Info del equipo --}}
                <div class="bg-cerberus-dark border border-cerberus-steel/50 rounded-lg px-4 py-3 mb-6
                            flex items-center gap-3 text-sm">
                    <span class="material-icons text-cerberus-accent">devices</span>
                    <div>
                        <p class="text-white font-medium">{{ $equipo->categoria->nombre }}</p>
                        <p class="text-cerberus-light text-xs">
                            Serial: {{ $equipo->serial ?? '—' }}
                            · Estado actual: {{ $equipo->estado->nombre }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm bg-cerberus-steel/30 hover:bg-cerberus-steel/50
                               text-white rounded-lg transition">
                        Cancelar
                    </button>

                    <button wire:click="desactivar"
                            wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg
                               transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="desactivar"
                              class="material-icons text-sm">do_not_disturb_on</span>
                        <span wire:loading wire:target="desactivar"
                              class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="desactivar">Dar de baja</span>
                        <span wire:loading wire:target="desactivar">Procesando...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>