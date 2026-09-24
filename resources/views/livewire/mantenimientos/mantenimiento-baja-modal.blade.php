<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 flex items-center gap-2">
                        <span class="material-icons">report</span>
                        Dar de baja el equipo
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40 rounded-lg px-4 py-3 text-sm text-red-700 dark:text-red-300">
                        Esta acción es <strong>permanente e irreversible</strong>. El equipo pasará a estado
                        «Dado de baja» y quedará excluido de asignación, préstamo y traslado. Si tenía una
                        asignación activa, se cerrará automáticamente con este motivo.
                    </div>

                    <x-form.textarea
                        label="Motivo de la baja"
                        wire:model="motivo"
                        rows="3"
                        placeholder="Ej: Pantalla y placa dañadas, costo de reparación supera el valor del equipo."
                        :error="$errors->first('motivo')"
                        required
                    />
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="confirmar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-red-600 hover:bg-red-700
                               text-white transition flex items-center gap-2 disabled:opacity-60">
                        <span class="material-icons text-sm">report</span>
                        Confirmar baja
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
