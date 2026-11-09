<div>
    @if ($open && $traslado)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrar"></div>

            <div class="relative z-50 w-full max-w-md p-6 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus">

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="material-icons text-red-400">delete_forever</span>
                    Eliminar Traslado
                </h2>

                <p class="text-cerberus-light mb-2">
                    ¿Seguro que deseas eliminar el traslado
                    <strong class="text-cerberus-accent">{{ $traslado->numero }}</strong>?
                </p>

                <div class="my-4 p-3 rounded-lg bg-cerberus-dark border border-cerberus-steel/50 text-sm space-y-1">
                    <div class="flex items-center gap-2 text-cerberus-light">
                        <span class="material-icons text-sm text-gray-400">location_on</span>
                        {{ $traslado->ubicacionOrigen?->nombre ?? '—' }}
                        <span class="material-icons text-sm text-gray-400">arrow_forward</span>
                        {{ $traslado->ubicacionDestino?->nombre ?? '—' }}
                    </div>
                    <div class="flex items-center gap-2 text-cerberus-light">
                        <span class="material-icons text-sm text-gray-400">event</span>
                        {{ $traslado->fecha_traslado?->format('d/m/Y') }}
                    </div>
                </div>

                <p class="text-xs text-cerberus-steel mb-6">
                    Esta acción no puede deshacerse. Los equipos no recuperarán su ubicación anterior automáticamente.
                </p>

                <div class="flex justify-end gap-3">
                    <button wire:click="cerrar"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition">
                        Cancelar
                    </button>
                    <button wire:click="confirmar"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg
                                   flex items-center gap-1.5 text-sm disabled:opacity-60 transition">
                        <span wire:loading.remove wire:target="confirmar" class="material-icons text-sm">delete_forever</span>
                        <span wire:loading       wire:target="confirmar" class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="confirmar">Eliminar</span>
                        <span wire:loading       wire:target="confirmar">Eliminando...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
