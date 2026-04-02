<div>
    @if ($open && $user)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div
                class="relative z-50 w-full max-w-md p-6 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="material-icons text-red-400">warning</span>
                    Eliminar Usuario
                </h2>

                <p class="text-cerberus-light mb-6">
                    ¿Seguro que deseas inactivar a <strong>{{ $user->name }}</strong>?
                </p>

                <div class="flex justify-end space-x-3">
                    <button wire:click="close" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center gap-1">
                        <span class="material-icons text-sm">delete</span>
                        Inactivar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
