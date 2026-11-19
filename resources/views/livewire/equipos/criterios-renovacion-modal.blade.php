<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-3xl mx-4 bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                        rounded-xl shadow-xl flex flex-col" style="max-height: 90vh;">

                <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent">tune</span> Criterios de renovación
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-cerberus-light">Cada tipo de equipo tiene los suyos. Pasa el cursor por el <span class="material-icons text-[14px] align-middle">help_outline</span> de cada campo para ver qué hace.</p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><span class="material-icons">close</span></button>
                </div>

                <div class="px-6 py-5 space-y-5 overflow-y-auto flex-1 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        <x-form.select label="Tipo de equipo" placeholder="Elige…" :options="$this->categorias" wire:model.live="categoriaId" />
                        @if ($f)
                            <label class="flex items-center gap-2 mb-4 cursor-pointer text-gray-700 dark:text-white">
                                <input type="checkbox" wire:model.live="f.evaluar" class="rounded text-cerberus-primary">
                                Evaluar este tipo de equipo
                            </label>
                        @endif
                    </div>

                    @if ($f && $f['evaluar'])
                        @php
                            $ayuda = 'text-xs text-gray-500 dark:text-cerberus-light -mt-3 mb-1';
                            $seccion = 'text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-cerberus-accent';
                        @endphp

                        {{-- De dónde salen los datos --}}
                        <div class="space-y-2">
                            <p class="{{ $seccion }}">De qué característica de la ficha sale cada dato</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                @foreach (['procesador' => 'Procesador', 'ram' => 'RAM', 'disco' => 'Disco', 'so' => 'Sistema operativo'] as $k => $titulo)
                                    <x-form.select :label="$titulo" :hint="'La característica de la ficha donde está el dato «' . $titulo . '». «Detectar sola» la busca por su nombre; elige otra si en este tipo de equipo se llama distinto. Si no existe, este criterio no se aplica.'" :placeholder="'Detectar sola' . (($this->detectados[$k] ?? null) ? ' (' . $this->detectados[$k] . ')' : ' (no encontrada)')"
                                        :options="$this->atributos" wire:model="f.atributo_{{ $k }}_id" />
                                @endforeach
                            </div>
                        </div>

                        {{-- Procesador --}}
                        <div class="space-y-2">
                            <p class="{{ $seccion }}">Procesador</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                <x-form.input label="Intel: generación mínima" hint="La generación de Intel Core más antigua que se acepta (ej. 8 = 8.ª generación). Un procesador más viejo vuelve obsoleto el equipo. Súbela cuando Windows o los programas de la empresa dejen de admitir esa generación." type="number" wire:model="f.intel_generacion_minima" :error="$errors->first('f.intel_generacion_minima')" />
                                <x-form.input label="AMD Ryzen: serie mínima" hint="La serie de AMD Ryzen más antigua que se acepta (ej. 2 = Ryzen 2000). Un procesador más viejo vuelve obsoleto el equipo. Súbela al mismo tiempo que la de Intel." type="number" wire:model="f.ryzen_serie_minima" :error="$errors->first('f.ryzen_serie_minima')" />
                            </div>
                        </div>

                        {{-- Edad --}}
                        <div class="space-y-2">
                            <p class="{{ $seccion }}">Edad</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                <x-form.input label="Revisar desde (años)" hint="Años de uso a partir de los cuales el equipo sale «por revisar». Se cuentan desde su fecha de adquisición; si no la tiene, se estiman por el año de su procesador." type="number" wire:model="f.edad_revisar" :error="$errors->first('f.edad_revisar')" />
                                <x-form.input label="Obsoleto desde (años)" hint="Años de uso a partir de los cuales el equipo es obsoleto. Solo cuenta la fecha de adquisición real: una edad estimada nunca lo vuelve obsoleto, solo «por revisar»." type="number" wire:model="f.edad_obsoleto" :error="$errors->first('f.edad_obsoleto')" />
                            </div>
                        </div>

                        {{-- RAM y disco --}}
                        <div class="space-y-2">
                            <p class="{{ $seccion }}">RAM y disco</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4">
                                <x-form.input label="RAM mínima (GB)" hint="Con menos RAM que esto (sumando todos sus módulos), el equipo sale repotenciable con la mejora «subir la RAM»." type="number" wire:model="f.ram_minima_gb" :error="$errors->first('f.ram_minima_gb')" />
                                <x-form.input label="Tipos de RAM obsoletos" hint="Separados por coma (ej. DDR, DDR2). Si el equipo usa uno de estos tipos, es obsoleto: ya no se consiguen módulos para mejorarlo. Agrega un tipo cuando deje de venderse." wire:model="f.ram_tipos_obsoletos" placeholder="DDR, DDR2" />
                                <x-form.input label="Disco mínimo (GB)" hint="Con menos espacio que esto (sumando todos sus discos), el equipo sale repotenciable con la mejora «disco más grande»." type="number" wire:model="f.disco_minimo_gb" :error="$errors->first('f.disco_minimo_gb')" />
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer text-gray-700 dark:text-white">
                                <input type="checkbox" wire:model="f.requiere_ssd" class="rounded text-cerberus-primary"> Recomendar SSD si solo tiene disco HDD
                                <span class="material-icons text-[16px] text-gray-400 cursor-help" title="Si todos sus discos son mecánicos (HDD), el equipo sale repotenciable con la mejora «cambiar a SSD».">help_outline</span>
                            </label>
                        </div>

                        {{-- Sistema operativo y reparaciones --}}
                        <div class="space-y-2">
                            <p class="{{ $seccion }}">Sistema operativo y reparaciones</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-gray-700 dark:text-white">
                                <span class="text-gray-500 dark:text-cerberus-light">Windows sin soporte:</span>
                                @foreach (\App\Livewire\Equipos\CriteriosRenovacionModal::SISTEMAS as $so)
                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                        <input type="checkbox" value="{{ $so }}" wire:model="f.so_sin_soporte" class="rounded text-cerberus-primary"> {{ $so }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="{{ $ayuda }} mt-0">Marca las versiones de Windows que ya no reciben actualizaciones de seguridad (y agrega la siguiente cuando le quiten el soporte). Un equipo con una de ellas es repotenciable si su procesador cumple el mínimo de arriba; si no, obsoleto.</p>
                            <div class="max-w-xs">
                                <x-form.input label="Reparaciones en 12 meses para obsoleto" hint="Cuántas reparaciones (casos correctivos) en el último año vuelven obsoleto al equipo: cuesta más mantenerlo que reemplazarlo." type="number" wire:model="f.reparaciones_max_12m" :error="$errors->first('f.reparaciones_max_12m')" />
                            </div>
                        </div>
                    @elseif ($f)
                        <p class="text-gray-500 dark:text-cerberus-light">Este tipo de equipo no se evalúa (no aparece en Renovación). Márcalo arriba para configurarlo.</p>
                    @endif
                </div>

                <div class="flex justify-between gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <button wire:click="sugeridos" class="text-sm text-cerberus-primary dark:text-cerberus-accent hover:underline">Volver a los valores sugeridos</button>
                    <div class="flex gap-3">
                        <button wire:click="close" class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">Cancelar</button>
                        <button wire:click="guardar" class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white flex items-center gap-2">
                            <span class="material-icons text-sm">save</span> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
