<div>
    @if ($open && $atributo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>
            <div class="relative z-50 w-full max-w-md mx-4 p-6 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-icons text-red-400">delete</span>
                    Eliminar atributo
                </h2>
                <p class="text-sm text-gray-600 dark:text-cerberus-light mb-1">
                    ¿Seguro que deseas eliminar
                    <strong class="text-gray-900 dark:text-white">{{ $atributo->nombre }}</strong>
                    de la categoría
                    <strong class="text-gray-900 dark:text-white">{{ $atributo->categoria->nombre }}</strong>?
                </p>

                @if ($totalValores > 0)
                    <div class="mt-3 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40
                                rounded-lg text-sm text-red-700 dark:text-red-400 flex items-start gap-2">
                        <span class="material-icons text-base mt-0.5">warning</span>
                        <span>
                            No se puede eliminar: hay <strong>{{ $totalValores }} valor(es)</strong>
                            registrado(s) en equipos para este atributo.
                        </span>
                    </div>
                @else
                    <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-1">
                        Esta acción no puede deshacerse.
                    </p>
                @endif

                <div class="flex justify-end gap-3 mt-5">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    @if ($totalValores === 0)
                        <button wire:click="eliminar" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm rounded-lg font-medium bg-red-600 hover:bg-red-700
                                   text-white transition flex items-center gap-2 disabled:opacity-60">
                            <span wire:loading.remove wire:target="eliminar" class="material-icons text-sm">delete</span>
                            <span wire:loading wire:target="eliminar" class="material-icons text-sm animate-spin">refresh</span>
                            <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                            <span wire:loading wire:target="eliminar">Eliminando...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>