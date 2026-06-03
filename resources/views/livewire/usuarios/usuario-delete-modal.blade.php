<div>
    @if ($open && $user)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrar"></div>

            <div class="relative z-50 w-full max-w-md p-6 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus">

                @if ($user->estado === 'Activo')
                    {{-- ── INACTIVAR ──────────────────────────────────────────── --}}
                    <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                        <span class="material-icons text-orange-400">person_off</span>
                        Inactivar Usuario
                    </h2>

                    <p class="text-cerberus-light mb-2">
                        ¿Seguro que deseas inactivar a <strong>{{ $user->name }}</strong>?
                    </p>
                    <p class="text-xs text-cerberus-steel mb-6">
                        El usuario no podrá iniciar sesión. Puedes reactivarlo desde esta misma acción.
                    </p>

                    <div class="flex justify-end space-x-3">
                        <button wire:click="cerrar"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm">
                            Cancelar
                        </button>
                        <button wire:click="confirmar"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg flex items-center gap-1 text-sm disabled:opacity-60">
                            <span wire:loading.remove wire:target="confirmar" class="material-icons text-sm">person_off</span>
                            <span wire:loading wire:target="confirmar" class="material-icons text-sm animate-spin">refresh</span>
                            <span wire:loading.remove wire:target="confirmar">Inactivar</span>
                            <span wire:loading wire:target="confirmar">Procesando...</span>
                        </button>
                    </div>

                @else
                    {{-- ── ACTIVAR ────────────────────────────────────────────── --}}
                    <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                        <span class="material-icons text-green-400">person</span>
                        Activar Usuario
                    </h2>

                    <p class="text-cerberus-light mb-2">
                        ¿Deseas reactivar a <strong>{{ $user->name }}</strong>?
                    </p>
                    <p class="text-xs text-cerberus-steel mb-6">
                        El usuario podrá volver a iniciar sesión en el sistema.
                    </p>

                    <div class="flex justify-end space-x-3">
                        <button wire:click="cerrar"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm">
                            Cancelar
                        </button>
                        <button wire:click="confirmar"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center gap-1 text-sm disabled:opacity-60">
                            <span wire:loading.remove wire:target="confirmar" class="material-icons text-sm">person</span>
                            <span wire:loading wire:target="confirmar" class="material-icons text-sm animate-spin">refresh</span>
                            <span wire:loading.remove wire:target="confirmar">Activar</span>
                            <span wire:loading wire:target="confirmar">Procesando...</span>
                        </button>
                    </div>
                @endif

            </div>
        </div>
    @endif
</div>
