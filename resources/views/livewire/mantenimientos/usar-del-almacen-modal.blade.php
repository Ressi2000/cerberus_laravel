<div>
    @if ($open && $this->mantenimiento)
        @php
            $m          = $this->mantenimiento;
            $componente = $this->componente;
            $granel     = $componente?->esAGranel();
            $sinSerial  = $componente && ! $granel ? $componente->unidadesSinIdentidad() : 0;
            $disponible = $componente ? $componente->unidadesParaConsumo() : 0;
            $hayStock   = $componente && (($granel || $piezaId === 'sin-serial') ? $disponible > 0 : ($this->piezas->isNotEmpty() || $sinSerial > 0));
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-2xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 90vh;">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent">inventory_2</span>
                            Usar del almacén
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent">Caso #{{ $m->id }} · {{ $m->equipo->codigo_interno ?? '' }}</p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                    @error('general')
                        <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-sm
                                    bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-500/30">
                            <span class="material-icons text-base">error_outline</span>{{ $message }}
                        </div>
                    @enderror

                    {{-- 1. Familia y variante --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <x-form.select label="Familia" placeholder="¿Qué tipo de pieza?"
                            :options="$this->familias->pluck('nombre', 'id')"
                            wire:model.live="familiaId" :error="$errors->first('familiaId')" />

                        @if ($this->familia)
                            <x-form.select label="Variante" placeholder="Elige…"
                                :options="$this->variantes->mapWithKeys(fn ($v) => [$v->id => $v->nombre . ' · ' . $v->unidadesParaConsumo() . ' disponible(s)'])->put('nueva', '+ No está en el almacén: solicitarla')"
                                wire:model.live="componenteId" :error="$errors->first('componenteId')" />
                        @endif
                    </div>

                    {{-- Solicitar una variante que no existe --}}
                    @if ($this->familia && $componenteId === 'nueva')
                        <div class="space-y-2 p-3 rounded-lg border border-purple-200 dark:border-purple-500/30 bg-purple-50/60 dark:bg-purple-500/10">
                            <p class="text-xs text-purple-700 dark:text-purple-300">Se crea en el almacén con stock 0 y queda como solicitud pendiente.</p>
                            @if ($this->familia->tieneFicha() && $this->familia->atributoRepresentativo())
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                    @foreach ($this->familia->campos as $campo)
                                        <div wire:key="sol-{{ $campo['id'] }}">
                                            @if (($campo['tipo'] ?? '') === 'select')
                                                <x-form.select :label="$campo['nombre']" placeholder="Elige…"
                                                    :options="collect($campo['opciones'] ?? [])->mapWithKeys(fn ($o) => [$o => $o])"
                                                    wire:model="valores.{{ $campo['id'] }}" />
                                            @else
                                                <x-form.input :label="$campo['nombre']" wire:model="valores.{{ $campo['id'] }}" />
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-form.input label="¿Qué necesitas?" wire:model="nuevoNombre" placeholder="Ej: Pantalla 14&quot; HD" />
                            @endif
                            <x-form.input label="Cantidad" type="number" wire:model="cantidad" :error="$errors->first('cantidad')" />
                        </div>
                    @endif

                    {{-- 2. Qué se usa --}}
                    @if ($componente)
                        @if ($granel)
                            <x-form.input label="¿Cuántas?" type="number" wire:model.live="cantidad" :error="$errors->first('cantidad')"
                                hint="{{ $disponible }} disponible(s). Si no alcanza, queda como solicitud pendiente." />
                        @else
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">¿Cuál pieza?</p>
                                @error('piezaId') <p class="text-red-500 text-xs mb-1">{{ $message }}</p> @enderror
                                <div class="max-h-56 overflow-y-auto space-y-1.5 border border-gray-200 dark:border-cerberus-steel/50 rounded-lg p-2">
                                    @foreach ($this->piezas as $p)
                                        <label wire:key="pz-{{ $p->id }}"
                                            class="flex items-start gap-2 text-sm rounded-md px-2 py-1.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-cerberus-steel/10
                                                   {{ $piezaId === (string) $p->id ? 'bg-blue-50 dark:bg-blue-500/10' : '' }}">
                                            <input type="radio" value="{{ $p->id }}" wire:model.live="piezaId" class="mt-1 text-cerberus-primary">
                                            <span class="text-gray-900 dark:text-white">
                                                <span class="font-mono text-xs">{{ $p->identificador ?? 'Sin serial' }}</span>
                                                <span class="block text-xs text-gray-500 dark:text-cerberus-light">
                                                    {{ $p->labelCondicion() }}
                                                    @if ($p->equipoOrigen) · de {{ $p->equipoOrigen->codigo_interno }} @endif
                                                    · ingresó {{ $p->created_at->format('d/m/Y') }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                    @if ($sinSerial > 0)
                                        <label class="flex items-start gap-2 text-sm rounded-md px-2 py-1.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-cerberus-steel/10">
                                            <input type="radio" value="sin-serial" wire:model.live="piezaId" class="mt-1 text-cerberus-primary">
                                            <span class="text-gray-900 dark:text-white">Sin serial <span class="text-xs text-gray-500 dark:text-cerberus-light">({{ $sinSerial }} unidad(es) antiguas)</span></span>
                                        </label>
                                    @endif
                                    @if ($this->piezas->isEmpty() && $sinSerial === 0)
                                        <p class="text-sm text-gray-500 dark:text-cerberus-light px-2 py-1">No hay unidades en el almacén. Puedes solicitarla.</p>
                                    @endif
                                </div>
                            </div>

                            @if ($piezaId === 'sin-serial' || ! $hayStock)
                                <x-form.input label="Cantidad" type="number" wire:model.live="cantidad" :error="$errors->first('cantidad')" />
                            @endif
                        @endif

                        {{-- 3. Ficha del equipo antes → después --}}
                        @if ($this->pieza && $this->atributoDestino)
                            <div class="space-y-3 p-3 rounded-lg border border-teal-200 dark:border-teal-500/30 bg-teal-50/50 dark:bg-teal-500/5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1.5">
                                    <span class="material-icons text-base text-teal-600 dark:text-teal-400">tune</span>
                                    Ficha del equipo · {{ $this->atributoDestino->nombre }}
                                </p>

                                <div class="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-cerberus-light">
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" value="agregar" wire:model.live="modo"> Agregar
                                    </label>
                                    @if ($this->fichaActual->isNotEmpty())
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" value="sustituir" wire:model.live="modo"> Reemplazar una existente
                                        </label>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-cerberus-accent mb-1">Antes</p>
                                        @forelse ($this->fichaActual as $inst)
                                            <label wire:key="inst-{{ $inst->id }}"
                                                class="flex items-center gap-2 px-2 py-1 rounded {{ $modo === 'sustituir' && $instanciaId === (string) $inst->id ? 'line-through text-gray-400' : 'text-gray-800 dark:text-white' }}">
                                                @if ($modo === 'sustituir')
                                                    <input type="radio" value="{{ $inst->id }}" wire:model.live="instanciaId">
                                                @endif
                                                {{ $this->atributoDestino->describirValor($inst->valores ?? []) }}
                                            </label>
                                        @empty
                                            <p class="text-gray-400 dark:text-cerberus-steel px-2">Vacía</p>
                                        @endforelse
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-cerberus-accent mb-1">Después</p>
                                        @foreach ($this->fichaActual as $inst)
                                            @unless ($modo === 'sustituir' && $instanciaId === (string) $inst->id)
                                                <p class="px-2 py-1 text-gray-800 dark:text-white">{{ $this->atributoDestino->describirValor($inst->valores ?? []) }}</p>
                                            @endunless
                                        @endforeach
                                        <p class="px-2 py-1 rounded font-medium bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-300">
                                            + {{ $this->pieza->descripcion() }}
                                        </p>
                                    </div>
                                </div>

                                @if ($modo === 'sustituir')
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                        <x-form.select label="La que sale, ¿en qué estado está?"
                                            :options="['en_almacen' => 'Buena → Almacén', 'en_deposito' => 'Dañada → Depósito']"
                                            wire:model.live="destinoSaliente" />
                                        @if ($destinoSaliente === 'en_deposito')
                                            <x-form.select label="Depósito" placeholder="Elige…" :options="$this->depositos"
                                                wire:model="depositoSaliente" :error="$errors->first('depositoSaliente')" />
                                        @endif
                                        <x-form.input label="Serial de la que sale (opcional)" wire:model="serialSaliente"
                                            placeholder="Si lo tiene a la vista" />
                                    </div>
                                @endif
                            </div>
                        @elseif ($this->pieza)
                            <p class="text-xs text-gray-500 dark:text-cerberus-light flex items-center gap-1.5">
                                <span class="material-icons text-sm">info</span>
                                Se descuenta del almacén y queda anotada en este caso, instalada en {{ $m->equipo->codigo_interno ?? 'el equipo' }}.
                            </p>
                        @endif
                    @endif
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    @if ($componenteId === 'nueva' || ($componente && ! $hayStock))
                        <button wire:click="solicitar" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm rounded-lg font-medium bg-purple-600 hover:bg-purple-700 text-white transition flex items-center gap-2 disabled:opacity-60">
                            <span class="material-icons text-sm">pending_actions</span> Solicitar al almacén
                        </button>
                    @elseif ($componente)
                        <button wire:click="usar" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition flex items-center gap-2 disabled:opacity-60">
                            <span class="material-icons text-sm">check</span> Usar en el equipo
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
