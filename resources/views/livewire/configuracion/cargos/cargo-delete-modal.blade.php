<div>
    @if ($open && $cargo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-md mx-4 p-6 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-icons text-yellow-400">block</span>
                    Desactivar cargo
                </h2>

                <p class="text-sm text-gray-600 dark:text-cerberus-light mb-1">
                    ¿Deseas desactivar el cargo
                    <strong class="text-gray-900 dark:text-white">{{ $cargo->nombre }}</strong>?
                </p>

                @if ($totalUsuarios > 0)
                    <div class="mt-3 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40
                                rounded-lg text-sm text-red-700 dark:text-red-400 flex items-start gap-2">
                        <span class="material-icons text-base mt-0.5">warning</span>
                        <span>
                            No se puede desactivar: tiene
                            <strong>{{ $totalUsuarios }} usuario(s) activo(s)</strong> con este cargo.
                        </span>
                    </div>
                @else
                    <div class="mt-3 px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/40
                                rounded-lg text-sm text-yellow-700 dark:text-yellow-400 flex items-start gap-2">
                        <span class="material-icons text-base mt-0.5">info</span>
                        <span>
                            El cargo dejará de aparecer en los formularios, pero
                            <strong>no se eliminará</strong>. Podrás reactivarlo desde esta tabla.
                        </span>
                    </div>
                @endif

                <div class="flex justify-end gap-3 mt-5">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>

                    @if ($totalUsuarios === 0)
                        <button wire:click="desactivar" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm rounded-lg font-medium bg-yellow-600 hover:bg-yellow-700
                                   text-white transition flex items-center gap-2 disabled:opacity-60">
                            <span wire:loading.remove wire:target="desactivar" class="material-icons text-sm">block</span>
                            <span wire:loading wire:target="desactivar" class="material-icons text-sm animate-spin">refresh</span>
                            <span wire:loading.remove wire:target="desactivar">Desactivar</span>
                            <span wire:loading wire:target="desactivar">Desactivando...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>