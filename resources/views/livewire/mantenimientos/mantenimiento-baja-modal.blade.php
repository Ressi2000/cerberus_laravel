<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 90vh;">

                <div class="flex items-center justify-between px-6 py-4 flex-shrink-0 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 flex items-center gap-2">
                        <span class="material-icons">report</span>
                        Dar de baja el equipo
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
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

                    <x-form.select
                        label="Depósito donde quedará guardado"
                        wire:model="depositoId"
                        :options="$depositos->pluck('nombre', 'id')"
                        placeholder="Selecciona un depósito..."
                        :error="$errors->first('depositoId')"
                        required
                    />

                    @if (count($candidatos) > 0)
                        <div class="pt-2 border-t border-gray-100 dark:border-cerberus-steel/30">
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide mb-2">
                                ¿Alguna pieza se puede rescatar?
                            </p>
                            <div class="space-y-2">
                                @foreach ($candidatos as $i => $c)
                                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-cerberus-steel/40 px-3 py-2">
                                        <label class="flex items-center gap-2 flex-1 min-w-0 cursor-pointer">
                                            <input type="checkbox" wire:model="seleccion.{{ $i }}.extraer"
                                                class="rounded border-gray-300 dark:border-cerberus-steel text-cerberus-primary focus:ring-cerberus-primary">
                                            <span class="text-sm text-gray-700 dark:text-cerberus-light truncate">{{ $c['descripcion'] }}</span>
                                        </label>
                                        <select wire:model="seleccion.{{ $i }}.destino"
                                            class="text-xs rounded-lg px-2 py-1 flex-shrink-0
                                                   bg-white dark:bg-cerberus-dark
                                                   border border-gray-300 dark:border-cerberus-steel
                                                   text-gray-700 dark:text-white">
                                            <option value="en_almacen">Buena → Almacén</option>
                                            <option value="en_deposito">Dañada → Depósito</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 flex-shrink-0 border-t border-gray-100 dark:border-cerberus-steel">
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
