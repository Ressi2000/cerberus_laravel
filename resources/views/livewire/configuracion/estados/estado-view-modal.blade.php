<div>
    @if ($open && $estado)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>
            <div class="relative z-50 w-full max-w-sm mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">flag</span>
                        Detalle del estado
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Nombre</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mt-0.5">{{ $estado->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Equipos con este estado</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $estado->equipos_count }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="$dispatch('openEstadoEditar', { id: {{ $estado->id }} }); close()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg font-medium
                               bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                               border border-amber-200 dark:border-amber-500/30
                               hover:bg-amber-100 dark:hover:bg-amber-500/20 transition">
                        <span class="material-icons text-base">edit</span> Editar
                    </button>
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>