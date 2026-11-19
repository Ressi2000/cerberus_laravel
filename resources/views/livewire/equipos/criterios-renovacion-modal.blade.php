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
                        <p class="text-sm text-gray-500 dark:text-cerberus-light">
                            Cada tipo de equipo tiene los suyos. Pasa el cursor por el <span class="material-icons text-[14px] align-middle">help_outline</span> de cada campo para ver qué hace.
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><span class="material-icons">close</span></button>
                </div>

                <div class="px-6 py-5 space-y-6 overflow-y-auto flex-1 text-sm">
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
                            $bloque  = 'rounded-xl border border-gray-200 dark:border-cerberus-steel/60 p-4 space-y-4';
                            $titulo  = 'text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2';
                            $sub     = 'text-xs text-gray-500 dark:text-cerberus-light';
                            $tecnicos = [
                                'procesador' => ['Procesador', 'memory'],
                                'so'         => ['Sistema operativo', 'desktop_windows'],
                                'ram'        => ['RAM', 'developer_board'],
                                'disco'      => ['Disco', 'storage'],
                            ];
                        @endphp

                        {{-- ═══ 1. Generales: sirven para cualquier tipo de equipo ═══ --}}
                        <div class="{{ $bloque }}">
                            <div>
                                <p class="{{ $titulo }}"><span class="material-icons text-base text-cerberus-accent">schedule</span> Criterios generales</p>
                                <p class="{{ $sub }}">Se aplican a cualquier tipo de equipo, tenga o no datos técnicos en su ficha.</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4">
                                <x-form.input label="Revisar desde (años)" type="number" wire:model="f.edad_revisar" :error="$errors->first('f.edad_revisar')"
                                    hint="Años de uso a partir de los cuales el equipo sale «por revisar». Se cuentan desde su fecha de adquisición; si no la tiene y se usa el procesador, se estiman por el año del procesador." />
                                <x-form.input label="Obsoleto desde (años)" type="number" wire:model="f.edad_obsoleto" :error="$errors->first('f.edad_obsoleto')"
                                    hint="Vida útil: años de uso a partir de los cuales el equipo es obsoleto. Solo cuenta la fecha de adquisición real; una edad estimada nunca lo vuelve obsoleto." />
                                <x-form.input label="Reparaciones en 12 meses" type="number" wire:model="f.reparaciones_max_12m" :error="$errors->first('f.reparaciones_max_12m')"
                                    hint="Cuántas reparaciones (casos correctivos) en el último año vuelven obsoleto al equipo: cuesta más mantenerlo que reemplazarlo." />
                            </div>
                        </div>

                        {{-- ═══ 2. Técnicos: cada uno se enciende si aplica a este tipo de equipo ═══ --}}
                        <div class="space-y-3">
                            <div>
                                <p class="{{ $titulo }}"><span class="material-icons text-base text-cerberus-accent">settings_suggest</span> Criterios técnicos</p>
                                <p class="{{ $sub }}">Enciende solo los que tienen sentido para este tipo de equipo. Cada uno lee su dato de una característica de la ficha.</p>
                            </div>

                            @foreach ($tecnicos as $k => [$nombre, $icono])
                                @php
                                    $encendido  = (bool) ($f['aplicar_' . $k] ?? false);
                                    $detectado  = $this->detectados[$k] ?? null;
                                    $sinDato    = ! $detectado && ($f['atributo_' . $k . '_id'] ?? '') === '';
                                @endphp
                                <div wire:key="tec-{{ $k }}" @class([$bloque, 'opacity-60' => ! $encendido])>
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="{{ $titulo }}"><span class="material-icons text-base text-cerberus-accent">{{ $icono }}</span> {{ $nombre }}</p>
                                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-white">
                                            <input type="checkbox" wire:model.live="f.aplicar_{{ $k }}" class="rounded text-cerberus-primary"> Aplicar a este tipo de equipo
                                        </label>
                                    </div>

                                    @if ($encendido)
                                        <x-form.select label="Sale de la característica" :placeholder="'Detectar sola' . ($detectado ? ' (' . $detectado . ')' : ' (no encontrada)')"
                                            :options="$this->atributos" wire:model.live="f.atributo_{{ $k }}_id"
                                            hint="La característica de la ficha donde está este dato. «Detectar sola» la busca por su nombre; elige otra si en este tipo de equipo se llama distinto." />

                                        @if ($sinDato)
                                            <p class="-mt-2 text-xs text-amber-700 dark:text-amber-300 flex items-start gap-1">
                                                <span class="material-icons text-sm">info</span>
                                                Este tipo de equipo no tiene «{{ $nombre }}» en su ficha: elige la característica donde está, o apaga este criterio.
                                            </p>
                                        @endif

                                        @if ($k === 'procesador')
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                                <x-form.input label="Intel: generación mínima" type="number" wire:model="f.intel_generacion_minima" :error="$errors->first('f.intel_generacion_minima')"
                                                    hint="La generación de Intel Core más antigua que se acepta (ej. 8 = 8.ª generación). Un procesador más viejo vuelve obsoleto el equipo. Súbela cuando Windows o los programas de la empresa dejen de admitir esa generación." />
                                                <x-form.input label="AMD Ryzen: serie mínima" type="number" wire:model="f.ryzen_serie_minima" :error="$errors->first('f.ryzen_serie_minima')"
                                                    hint="La serie de AMD Ryzen más antigua que se acepta (ej. 2 = Ryzen 2000). Un procesador más viejo vuelve obsoleto el equipo. Súbela al mismo tiempo que la de Intel." />
                                            </div>
                                        @elseif ($k === 'so')
                                            <div>
                                                <p class="text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1 flex items-center gap-1.5">
                                                    Windows sin soporte
                                                    <span class="material-icons text-[16px] text-gray-400 cursor-help"
                                                          title="Versiones de Windows que ya no reciben actualizaciones de seguridad (agrega la siguiente cuando le quiten el soporte). Un equipo con una de ellas es repotenciable si su procesador cumple el mínimo; si no, obsoleto.">help_outline</span>
                                                </p>
                                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-gray-700 dark:text-white">
                                                    @foreach (\App\Livewire\Equipos\CriteriosRenovacionModal::SISTEMAS as $so)
                                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                                            <input type="checkbox" value="{{ $so }}" wire:model="f.so_sin_soporte" class="rounded text-cerberus-primary"> {{ $so }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @elseif ($k === 'ram')
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                                <x-form.input label="RAM mínima (GB)" type="number" wire:model="f.ram_minima_gb" :error="$errors->first('f.ram_minima_gb')"
                                                    hint="Con menos RAM que esto (sumando todos sus módulos), el equipo sale repotenciable con la mejora «subir la RAM»." />
                                                <x-form.input label="Tipos de RAM obsoletos" wire:model="f.ram_tipos_obsoletos" placeholder="DDR, DDR2"
                                                    hint="Separados por coma (ej. DDR, DDR2). Si el equipo usa uno de estos tipos, es obsoleto: ya no se consiguen módulos para mejorarlo. Agrega un tipo cuando deje de venderse." />
                                            </div>
                                        @elseif ($k === 'disco')
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 items-center">
                                                <x-form.input label="Disco mínimo (GB)" type="number" wire:model="f.disco_minimo_gb" :error="$errors->first('f.disco_minimo_gb')"
                                                    hint="Con menos espacio que esto (sumando todos sus discos), el equipo sale repotenciable con la mejora «disco más grande»." />
                                                <label class="flex items-center gap-2 cursor-pointer text-gray-700 dark:text-white">
                                                    <input type="checkbox" wire:model="f.requiere_ssd" class="rounded text-cerberus-primary"> Recomendar SSD si solo tiene HDD
                                                    <span class="material-icons text-[16px] text-gray-400 cursor-help" title="Si todos sus discos son mecánicos (HDD), el equipo sale repotenciable con la mejora «cambiar a SSD».">help_outline</span>
                                                </label>
                                            </div>
                                        @endif
                                    @else
                                        <p class="{{ $sub }}">No se aplica a este tipo de equipo.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif ($f)
                        <p class="text-gray-500 dark:text-cerberus-light">Este tipo de equipo no se evalúa (no aparece en Renovación). Márcalo arriba para configurarlo.</p>
                    @endif
                </div>

                <div class="flex justify-between gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <button wire:click="sugeridos" class="text-sm text-cerberus-primary dark:text-cerberus-accent hover:underline"
                            title="Vuelve los números a los valores sugeridos (no cambia qué criterios están encendidos).">Volver a los valores sugeridos</button>
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
