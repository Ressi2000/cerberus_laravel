<div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="material-icons text-cerberus-accent text-base">memory</span>
            Piezas del equipo
        </h3>
        @if ($this->mantenimiento->permiteRegistrarTrabajo())
            <div class="flex items-center gap-3">
                @if (count($this->candidatosRetiro) > 0)
                    <button wire:click="abrirRetirar"
                        class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline flex items-center gap-1">
                        <span class="material-icons text-sm">remove_circle_outline</span> Retirar pieza
                    </button>
                @endif
                @if (count($this->piezasDisponibles) > 0)
                    <button wire:click="abrirInstalar"
                        class="text-xs font-medium text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1">
                        <span class="material-icons text-sm">add_circle_outline</span> Instalar pieza rescatada
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- ── Retirar pieza ────────────────────────────────────────────────── --}}
    @if ($retirarAbierto)
        <div class="bg-gray-50 dark:bg-cerberus-dark/50 border border-gray-200 dark:border-cerberus-steel/50 rounded-lg p-4 mb-4 space-y-3">
            <x-form.select
                label="Pieza a retirar"
                placeholder="Selecciona..."
                :options="collect($this->candidatosRetiro)->pluck('descripcion')"
                wire:model="retirarIndex"
                :error="$errors->first('retirarIndex')"
            />

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">¿Está en buen estado?</label>
                <select wire:model.live="retirarDestino"
                    class="w-full rounded-lg px-4 py-2 text-sm bg-white dark:bg-cerberus-dark
                           border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white">
                    <option value="en_almacen">Buena → Almacén (reutilizable)</option>
                    <option value="en_deposito">Dañada → Depósito (descarte)</option>
                </select>
            </div>

            @if ($retirarDestino === 'en_deposito')
                <x-form.select
                    label="Depósito"
                    placeholder="Selecciona un depósito..."
                    :options="$depositos->pluck('nombre', 'id')"
                    wire:model="retirarDepositoId"
                    :error="$errors->first('retirarDepositoId')"
                />
            @endif

            <x-form.textarea label="Observaciones (opcional)" wire:model="retirarObservaciones" rows="2" />

            <div class="flex justify-end gap-2">
                <button wire:click="cerrarRetirar" class="px-3 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="retirar" class="px-3 py-1.5 text-xs rounded-lg bg-red-600 text-white">
                    Retirar
                </button>
            </div>
        </div>
    @endif

    {{-- ── Instalar pieza rescatada ─────────────────────────────────────── --}}
    @if ($instalarAbierto)
        <div class="bg-gray-50 dark:bg-cerberus-dark/50 border border-gray-200 dark:border-cerberus-steel/50 rounded-lg p-4 mb-4 space-y-3">
            <x-form.select
                label="Pieza rescatada disponible"
                placeholder="Selecciona..."
                :options="collect($this->piezasDisponibles)->pluck('descripcion', 'id')"
                wire:model.live="instalarPiezaId"
                :error="$errors->first('instalarPiezaId')"
            />

            @if ($instalarPiezaId)
                @php($esGrupo = collect($this->piezasDisponibles)->firstWhere('id', $instalarPiezaId)['es_grupo'] ?? false)

                @if ($esGrupo)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">¿Cómo se instala?</label>
                        <select wire:model.live="instalarModo"
                            class="w-full rounded-lg px-4 py-2 text-sm bg-white dark:bg-cerberus-dark
                                   border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white">
                            <option value="agregar">Se agrega junto a lo que ya tiene</option>
                            <option value="sustituir">Sustituye una pieza existente</option>
                        </select>
                    </div>

                    @if ($instalarModo === 'sustituir' && count($this->instanciasParaSustituir) > 0)
                        <x-form.select
                            label="¿Cuál reemplaza?"
                            placeholder="Selecciona..."
                            :options="$this->instanciasParaSustituir"
                            wire:model="instalarInstanciaId"
                            :error="$errors->first('instalarInstanciaId')"
                        />
                    @endif
                @else
                    <p class="text-xs text-gray-500 dark:text-cerberus-light flex items-center gap-1.5">
                        <span class="material-icons text-sm">info</span>
                        Sustituye el valor actual de este atributo en el equipo.
                    </p>
                @endif
            @endif

            <div class="flex justify-end gap-2">
                <button wire:click="cerrarInstalar" class="px-3 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="instalar" class="px-3 py-1.5 text-xs rounded-lg bg-[#1E40AF] text-white">
                    Instalar
                </button>
            </div>
        </div>
    @endif

    {{-- ── Historial de piezas de este caso ─────────────────────────────── --}}
    @forelse ($this->historial as $mov)
        <div wire:key="pieza-mov-{{ $mov->id }}" class="flex items-center justify-between py-2.5 {{ ! $loop->last ? 'border-b border-gray-100 dark:border-cerberus-steel/30' : '' }}">
            <div>
                <p class="text-sm text-gray-900 dark:text-white">
                    {{ $mov->pieza?->atributo?->describirValor($mov->pieza->valor_extraido) ?? 'Pieza' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-cerberus-light">{{ $mov->labelTipo() }}</p>
            </div>
            <span class="text-xs text-gray-400 dark:text-cerberus-steel">{{ $mov->created_at->format('d/m/Y H:i') }}</span>
        </div>
    @empty
        <p class="text-sm text-gray-400 dark:text-cerberus-steel text-center py-4">No se han retirado ni instalado piezas en este caso.</p>
    @endforelse
</div>
