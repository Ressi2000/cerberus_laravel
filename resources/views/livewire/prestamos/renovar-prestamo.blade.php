<div>
    @if ($abierto)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-data x-on:keydown.escape.window="$wire.cerrar()">

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrar"></div>

        {{-- Modal --}}
        <div class="relative z-10 w-full max-w-md
                    bg-white dark:bg-cerberus-mid
                    rounded-2xl shadow-2xl
                    border border-gray-200 dark:border-cerberus-steel/60
                    p-6 space-y-5">

            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-blue-500">event</span>
                        Renovar préstamo
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-cerberus-steel mt-1">
                        Receptor: <strong class="text-gray-800 dark:text-cerberus-light">{{ $receptorNombre }}</strong>
                    </p>
                </div>
                <button wire:click="cerrar"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-cerberus-light transition">
                    <span class="material-icons">close</span>
                </button>
            </div>

            @if ($estadoActual === 'Vencido')
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg
                            bg-amber-50 dark:bg-amber-900/20
                            border border-amber-200 dark:border-amber-700/40
                            text-amber-700 dark:text-amber-400 text-sm">
                    <span class="material-icons text-base">schedule</span>
                    Este préstamo está vencido. Al renovar volverá a estado <strong>Activo</strong>.
                </div>
            @endif

            @error('general')
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg
                            bg-red-50 dark:bg-red-900/30
                            border border-red-200 dark:border-red-700/50
                            text-red-700 dark:text-red-300 text-sm">
                    <span class="material-icons text-base">error_outline</span>
                    {{ $message }}
                </div>
            @enderror

            <div>
                <x-form.input
                    type="date"
                    label="Nueva fecha de devolución esperada"
                    wire:model.live="nuevaFecha"
                />
                @error('nuevaFecha')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 justify-end pt-1">
                <button wire:click="cerrar"
                        class="px-5 py-2 rounded-xl text-sm font-medium
                               bg-gray-100 dark:bg-cerberus-dark
                               text-gray-700 dark:text-cerberus-light
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/30 transition">
                    Cancelar
                </button>
                <button wire:click="confirmar"
                        wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-5 py-2 rounded-xl text-sm font-semibold
                               bg-blue-600 hover:bg-blue-700 text-white
                               disabled:opacity-50 transition">
                    <span class="material-icons text-base">check</span>
                    <span wire:loading.remove wire:target="confirmar">Confirmar renovación</span>
                    <span wire:loading wire:target="confirmar">Guardando...</span>
                </button>
            </div>

        </div>
    </div>
    @endif
</div>
