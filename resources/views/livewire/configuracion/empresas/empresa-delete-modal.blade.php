<div>
    @if ($open && $empresa)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-md mx-4 p-6 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-icons text-red-400">delete</span>
                    Eliminar empresa
                </h2>

                <p class="text-sm text-gray-600 dark:text-cerberus-light mb-1">
                    ¿Seguro que deseas eliminar
                    <strong class="text-gray-900 dark:text-white">{{ $empresa->nombre }}</strong>?
                </p>

                <p class="text-xs text-gray-500 dark:text-cerberus-steel mt-1">
                    Esta acción es reversible desde la administración del sistema.
                </p>

                {{-- Advertencia de impacto si tiene recursos vinculados --}}
                @if ($totalUsuarios > 0 || $totalEquipos > 0)
                    <div class="mt-3 px-4 py-3 bg-amber-50 dark:bg-amber-900/20
                                border border-amber-200 dark:border-amber-700/40
                                rounded-lg text-sm text-amber-700 dark:text-amber-400 space-y-1">
                        <div class="flex items-center gap-2 font-medium">
                            <span class="material-icons text-base">warning</span>
                            Esta empresa tiene recursos activos:
                        </div>
                        @if ($totalUsuarios > 0)
                            <p class="pl-6 text-xs">
                                • <strong>{{ $totalUsuarios }} usuario(s)</strong> con esta empresa como nómina.
                            </p>
                        @endif
                        @if ($totalEquipos > 0)
                            <p class="pl-6 text-xs">
                                • <strong>{{ $totalEquipos }} equipo(s)</strong> registrados en esta empresa.
                            </p>
                        @endif
                    </div>
                @endif

                <div class="flex justify-end gap-3 mt-5">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg
                               bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="eliminar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-red-600 hover:bg-red-700
                               text-white transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="eliminar" class="material-icons text-sm">delete</span>
                        <span wire:loading wire:target="eliminar" class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                        <span wire:loading wire:target="eliminar">Eliminando...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>