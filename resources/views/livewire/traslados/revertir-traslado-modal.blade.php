<div>
    @if ($open && $traslado)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrar"></div>

            <div class="relative z-50 w-full max-w-md p-6 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus">

                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="material-icons text-amber-400">undo</span>
                    Regresar Traslado
                </h2>

                <p class="text-cerberus-light mb-2">
                    ¿Deseas revertir el traslado
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

                <p class="text-xs text-amber-400/80 mb-4">
                    Los equipos serán devueltos a la ubicación de origen automáticamente.
                </p>

                <div class="mb-5">
                    <label class="block text-xs font-medium text-cerberus-steel mb-1.5">
                        Motivo de la reversión <span class="text-red-400">*</span>
                    </label>
                    <textarea
                        wire:model="motivoReversion"
                        rows="3"
                        placeholder="Explica por qué se revierte el traslado..."
                        class="w-full px-3 py-2 rounded-lg text-sm
                               bg-cerberus-dark border border-cerberus-steel
                               text-white placeholder-cerberus-steel/60
                               focus:border-cerberus-accent focus:ring-1 focus:ring-cerberus-accent
                               transition resize-none"
                    ></textarea>
                    @error('motivoReversion')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="cerrar"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition">
                        Cancelar
                    </button>
                    <button wire:click="confirmar"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg
                                   flex items-center gap-1.5 text-sm disabled:opacity-60 transition">
                        <span wire:loading.remove wire:target="confirmar" class="material-icons text-sm">undo</span>
                        <span wire:loading       wire:target="confirmar" class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="confirmar">Regresar traslado</span>
                        <span wire:loading       wire:target="confirmar">Procesando...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
