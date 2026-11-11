<div>
    @if ($open && $this->atributo)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-2xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 90vh;">

                {{-- Encabezado --}}
                <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent">link</span>
                            Vincular a una familia
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-cerberus-accent mt-0.5">
                            «{{ $this->atributo->nombre }}» de {{ $this->atributo->categoria->nombre ?? 'la categoría' }}
                            pasará a usar la ficha de la familia. Sus valores ya cargados se convierten a los campos de la familia.
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition flex-shrink-0">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5 overflow-y-auto flex-1">

                    {{-- 1. Familia --}}
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent">1. Familia</label>
                        <select wire:model.live="familiaId"
                            class="w-full rounded-lg px-3 py-2 text-sm bg-white dark:bg-cerberus-dark
                                   border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white">
                            <option value="">Selecciona una familia…</option>
                            @foreach ($this->familias as $fam)
                                <option value="{{ $fam->id }}">{{ $fam->nombre }}</option>
                            @endforeach
                        </select>
                        @error('familiaId') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    {{-- 2. De dónde sale cada campo --}}
                    @if ($this->familia)
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent">2. ¿De dónde sale cada campo?</label>
                            <p class="text-xs text-gray-500 dark:text-cerberus-light">
                                Se sugiere según el nombre. Un campo nuevo, como «Formato», puede tomar el mismo valor para todos (ej. SO-DIMM en Laptop).
                            </p>

                            @foreach ($this->familia->campos ?? [] as $campo)
                                @php $fuente = $mapeo[$campo['id']]['fuente'] ?? 'vacio'; @endphp
                                <div wire:key="map-{{ $campo['id'] }}"
                                     class="grid gap-2 items-center p-2.5 rounded-lg border border-gray-200 dark:border-cerberus-steel/40
                                            bg-gray-50 dark:bg-cerberus-dark/40"
                                     style="grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);">
                                    <div class="text-sm min-w-0">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $campo['nombre'] }}</span>
                                        @if ($campo['debe_coincidir'] ?? false)
                                            <span class="material-icons text-xs text-teal-600 dark:text-teal-400 align-middle" title="Debe coincidir para que la pieza encaje">rule</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-1.5 min-w-0">
                                        <select wire:model.live="mapeo.{{ $campo['id'] }}.fuente"
                                            class="w-full rounded-lg px-2 py-1.5 text-sm bg-white dark:bg-cerberus-dark
                                                   border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white">
                                            @foreach ($this->subCampos as $sc)
                                                <option value="sub:{{ $sc['id'] }}">Copiar de «{{ $sc['nombre'] }}»</option>
                                            @endforeach
                                            <option value="fijo">El mismo valor para todos…</option>
                                            <option value="vacio">Dejar vacío</option>
                                        </select>

                                        @if ($fuente === 'fijo')
                                            @if (($campo['tipo'] ?? '') === 'select')
                                                <select wire:model.live="mapeo.{{ $campo['id'] }}.valor"
                                                    class="w-full rounded-lg px-2 py-1.5 text-sm bg-white dark:bg-cerberus-dark
                                                           border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white">
                                                    <option value="">Elige…</option>
                                                    @foreach ($campo['opciones'] ?? [] as $op)
                                                        <option value="{{ $op }}">{{ $op }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" wire:model.live.500ms="mapeo.{{ $campo['id'] }}.valor"
                                                    class="w-full rounded-lg px-2 py-1.5 text-sm bg-white dark:bg-cerberus-dark
                                                           border border-gray-300 dark:border-cerberus-steel text-gray-900 dark:text-white"
                                                    placeholder="Valor para todos">
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @error('mapeo')
                                <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-sm
                                            bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300
                                            border border-red-200 dark:border-red-500/30">
                                    <span class="material-icons text-base">error_outline</span>{{ $message }}
                                </div>
                            @enderror
                        </div>
                    @endif

                    {{-- 3. Vista previa --}}
                    @if ($vistaPrevia)
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent">3. Vista previa (todavía no se guardó nada)</label>
                            <p class="text-sm text-gray-700 dark:text-cerberus-light">
                                Se convertirán <b>{{ $vistaPrevia['instancias_vigentes'] }}</b> registro(s) vigentes de equipos,
                                <b>{{ $vistaPrevia['instancias_historial'] }}</b> del historial y
                                <b>{{ $vistaPrevia['piezas'] }}</b> pieza(s) del almacén.
                            </p>

                            @if (! empty($vistaPrevia['ejemplos']))
                                <div class="rounded-lg border border-gray-200 dark:border-cerberus-steel/40 overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-left text-gray-500 dark:text-cerberus-accent">
                                                <th class="px-3 py-2 font-semibold">Antes</th>
                                                <th class="px-3 py-2 font-semibold">Después</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($vistaPrevia['ejemplos'] as [$antes, $despues])
                                                <tr class="border-t border-gray-100 dark:border-cerberus-steel/30">
                                                    <td class="px-3 py-2 text-gray-600 dark:text-cerberus-light">{{ $antes }}</td>
                                                    <td class="px-3 py-2 text-gray-900 dark:text-white font-medium">{{ $despues }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @foreach ($vistaPrevia['fuera_de_lista'] as $campoNombre => $valores)
                                <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs
                                            bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300
                                            border border-amber-200 dark:border-amber-500/30">
                                    <span class="material-icons text-sm">warning</span>
                                    <span>«{{ $campoNombre }}» tiene valores que no están en la lista de la familia: {{ implode(', ', $valores) }}.
                                        Se conservan tal cual; agrégalos a la lista de la familia si son correctos.</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Pie --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    @if ($this->familia)
                        @if (! $vistaPrevia)
                            <button wire:click="previsualizar" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition
                                       flex items-center gap-2 disabled:opacity-60">
                                <span class="material-icons text-sm">visibility</span> Ver vista previa
                            </button>
                        @else
                            <button wire:click="confirmar" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white transition
                                       flex items-center gap-2 disabled:opacity-60">
                                <span class="material-icons text-sm">check</span> Convertir y vincular
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
