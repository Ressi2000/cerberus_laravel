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
            </div>
        @endif
    </div>

    {{-- ── Retirar pieza ────────────────────────────────────────────────── --}}
    @if ($retirarAbierto)
        <div class="bg-gray-50 dark:bg-cerberus-dark/50 border border-gray-200 dark:border-cerberus-steel/50 rounded-lg p-4 mb-4">
          <div class="max-w-2xl space-y-3">
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">Pieza a retirar</p>
                <div class="space-y-1 rounded-lg border border-gray-200 dark:border-cerberus-steel/50 bg-white dark:bg-cerberus-dark p-1.5">
                    @foreach ($this->candidatosRetiro as $i => $candidato)
                        <label wire:key="retiro-{{ $i }}"
                            @class([
                                'flex items-center gap-2 text-sm rounded-md px-2 py-1.5 cursor-pointer transition',
                                'bg-blue-50 dark:bg-cerberus-primary/20' => $retirarIndex === $i,
                                'hover:bg-gray-50 dark:hover:bg-cerberus-steel/10' => $retirarIndex !== $i,
                            ])>
                            <input type="radio" value="{{ $i }}" wire:model.live="retirarIndex" class="text-cerberus-primary">
                            <span class="text-gray-900 dark:text-white">{{ $candidato['descripcion'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('retirarIndex') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @if ($this->piezasDelRetiro->count() > 1)
                <x-form.select
                    label="¿Cuál de las iguales sale?"
                    placeholder="Elige por su serial..."
                    :options="$this->piezasDelRetiro->mapWithKeys(fn ($p) => [$p->id => $p->codigo()])"
                    wire:model="retirarPiezaId"
                    :error="$errors->first('retirarPiezaId')"
                />
            @elseif ($this->piezasDelRetiro->count() === 1)
                <p class="text-xs text-gray-500 dark:text-cerberus-light">
                    Es la pieza <span class="font-mono">{{ $this->piezasDelRetiro->first()->codigo() }}</span>: seguirá con su mismo historial.
                </p>
            @endif

            @if ($retirarIndex !== null
                && ($this->candidatosRetiro[$retirarIndex]['tipo'] ?? null) !== 'pieza'
                && ! $this->piezasDelRetiro->contains(fn ($p) => $p->identificador))
                <x-form.input label="Serial (opcional)" wire:model="retirarSerial" placeholder="Si lo tiene a la vista" />
            @endif

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

          </div>
            <div class="flex justify-end gap-2 mt-3">
                <button wire:click="cerrarRetirar" class="px-3 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="retirar" class="px-3 py-1.5 text-xs rounded-lg bg-red-600 text-white">
                    Retirar
                </button>
            </div>
        </div>
    @endif


    {{-- ── Historial de piezas de este caso ─────────────────────────────── --}}
    @forelse ($this->historial as $mov)
        <div wire:key="pieza-mov-{{ $mov->id }}" class="flex items-center justify-between py-2.5 {{ ! $loop->last ? 'border-b border-gray-100 dark:border-cerberus-steel/30' : '' }}">
            <div>
                <p class="text-sm text-gray-900 dark:text-white">
                    {{ $mov->pieza?->atributo?->describirValor($mov->pieza->valor_extraido) ?? $mov->pieza?->componenteAlmacen?->nombre ?? 'Pieza' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-cerberus-light">
                    {{ $mov->labelTipo() }}
                    @if ($mov->pieza)
                        · <button type="button" wire:click="$dispatch('openPiezaFicha', { piezaId: {{ $mov->pieza->id }} })"
                              class="font-mono text-cerberus-primary dark:text-cerberus-accent hover:underline">{{ $mov->pieza->codigo() }}</button>
                    @endif
                </p>
            </div>
            <span class="text-xs text-gray-400 dark:text-cerberus-steel">{{ $mov->created_at->format('d/m/Y H:i') }}</span>
        </div>
    @empty
        <p class="text-sm text-gray-400 dark:text-cerberus-steel text-center py-4">No se han retirado ni instalado piezas en este caso.</p>
    @endforelse
</div>
