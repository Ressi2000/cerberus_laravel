<div>
    @if($open && $equipo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            {{-- Modal --}}
            <div class="relative z-50 w-full max-w-lg mx-4 bg-cerberus-mid border border-cerberus-steel
                        rounded-xl shadow-cerberus flex flex-col" style="max-height: 90vh;">

              <div class="p-6 pb-0 flex-shrink-0">
                <h2 class="text-xl font-semibold text-cerberus-light mb-2 flex items-center gap-2">
                    <span class="material-icons text-red-400">do_not_disturb_on</span>
                    Dar de baja equipo
                </h2>

                <p class="text-cerberus-light text-sm mb-1">
                    ¿Seguro que deseas dar de baja el equipo
                    <strong class="text-cerberus-light font-mono">{{ $equipo->codigo_interno }}</strong>?
                </p>

                <p class="text-cerberus-steel text-xs mb-4">
                    El equipo quedará marcado como
                    <strong class="text-red-400">Dado de baja</strong>
                    y no podrá ser asignado ni editado.
                    El registro permanece para auditoría e historial.
                </p>
              </div>

              <div class="px-6 space-y-4 overflow-y-auto flex-1">

                {{-- Info del equipo --}}
                <div class="bg-cerberus-dark border border-cerberus-steel/50 rounded-lg px-4 py-3
                            flex items-center gap-3 text-sm">
                    <span class="material-icons text-cerberus-accent">devices</span>
                    <div>
                        <p class="text-cerberus-light font-medium">{{ $equipo->categoria->nombre }}</p>
                        <p class="text-cerberus-light text-xs">
                            Estado actual: {{ $equipo->estado->nombre }}
                        </p>
                    </div>
                </div>

                <x-form.select
                    label="Depósito donde quedará guardado"
                    wire:model="depositoId"
                    :options="$depositos->pluck('nombre', 'id')"
                    placeholder="Selecciona un depósito..."
                    :error="$errors->first('depositoId')"
                    required
                />

                @if (count($candidatos) > 0)
                    <div class="pt-1">
                        <p class="text-xs font-medium text-cerberus-accent uppercase tracking-wide mb-2">
                            ¿Alguna pieza se puede rescatar?
                        </p>
                        <div class="space-y-2">
                            @foreach ($candidatos as $i => $c)
                                <div class="flex items-center gap-3 rounded-lg border border-cerberus-steel/50 px-3 py-2 bg-cerberus-dark">
                                    <label class="flex items-center gap-2 flex-1 min-w-0 cursor-pointer">
                                        <input type="checkbox" wire:model="seleccion.{{ $i }}.extraer"
                                            class="rounded border-cerberus-steel text-cerberus-primary focus:ring-cerberus-primary">
                                        <span class="text-sm text-cerberus-light truncate">{{ $c['descripcion'] }}</span>
                                    </label>
                                    <select wire:model="seleccion.{{ $i }}.destino"
                                        class="text-xs rounded-lg px-2 py-1 flex-shrink-0
                                               bg-cerberus-mid border border-cerberus-steel text-cerberus-light">
                                        <option value="en_almacen">Buena → Almacén</option>
                                        <option value="en_deposito">Dañada → Depósito</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <x-form.textarea
                    label="Observaciones (opcional)"
                    wire:model="observaciones"
                    rows="2"
                    placeholder="Notas adicionales sobre la baja..."
                />
              </div>

              <div class="flex justify-end gap-3 p-6 pt-4 flex-shrink-0">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm bg-cerberus-steel/30 hover:bg-cerberus-steel/50
                               text-gray-900 dark:text-white rounded-lg transition">
                        Cancelar
                    </button>

                    <button wire:click="desactivar"
                            wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg
                               transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="desactivar"
                              class="material-icons text-sm">do_not_disturb_on</span>
                        <span wire:loading wire:target="desactivar"
                              class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="desactivar">Dar de baja</span>
                        <span wire:loading wire:target="desactivar">Procesando...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>