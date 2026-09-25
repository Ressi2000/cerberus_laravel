<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-amber-500">report_problem</span>
                        Reportar problema encontrado
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="flex items-start gap-2 text-sm text-amber-700 dark:text-amber-400
                                bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30
                                rounded-lg px-4 py-3">
                        <span class="material-icons text-base flex-shrink-0">info</span>
                        <span>
                            Esto <strong>completa el mantenimiento preventivo</strong> y abre un caso de
                            <strong>reparación (correctivo)</strong> nuevo para este equipo, enlazado a este
                            mantenimiento.
                        </span>
                    </div>

                    <x-form.textarea
                        label="Describe el problema encontrado"
                        wire:model="falla_reportada"
                        rows="4"
                        placeholder="Ej: El ventilador hace ruido excesivo y la batería no retiene carga..."
                        :error="$errors->first('falla_reportada')"
                        required
                    />
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="guardar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-amber-600 hover:bg-amber-700
                               text-white transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="guardar" class="material-icons text-sm">report_problem</span>
                        <span wire:loading wire:target="guardar" class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="guardar">Reportar y abrir reparación</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
