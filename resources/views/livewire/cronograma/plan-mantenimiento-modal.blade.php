<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        max-h-[90vh] flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 flex-shrink-0 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $planId ? 'edit' : 'event_repeat' }}</span>
                        {{ $planId ? 'Editar plan de mantenimiento' : 'Nuevo plan de mantenimiento' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">

                    @if (Auth::user()->hasRole('Administrador'))
                        <x-form.select
                            label="Empresa"
                            placeholder="Selecciona una empresa"
                            :options="$this->empresasOpciones"
                            wire:model.live="empresa_id"
                            :error="$errors->first('empresa_id')"
                            required
                        />
                    @endif

                    <div wire:key="plan-categoria-select-{{ $empresa_id ?: 'sin-empresa' }}">
                        <x-form.select
                            searchable
                            label="Categoría"
                            placeholder="{{ $empresa_id ? 'Selecciona una categoría' : 'Primero selecciona la empresa' }}"
                            :options="$this->categoriasOpciones"
                            :selected="$categoria_id"
                            wire:model.live="categoria_id"
                            :error="$errors->first('categoria_id')"
                            :disabled="! $empresa_id || $planId"
                            hint="El plan aplica a TODOS los equipos activos de esta categoría, en esta empresa."
                            required
                        />
                    </div>

                    <div wire:key="plan-depto-select-{{ $empresa_id ?: 'sin-empresa' }}">
                        <x-form.select
                            searchable
                            label="Departamento (opcional)"
                            placeholder="Todos los departamentos"
                            :options="$this->departamentosOpciones"
                            :selected="$departamento_id"
                            wire:model.live="departamento_id"
                            :error="$errors->first('departamento_id')"
                            :disabled="! $empresa_id || $planId"
                            hint="Acota el plan a los equipos cuyo receptor actual (persona o área) pertenece a este departamento — útil para organizar la logística de entrega por jornadas."
                        />
                    </div>

                    @if ($this->equiposAlcanzadosCount !== null)
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-cerberus-light
                                    bg-gray-50 dark:bg-cerberus-dark/40 rounded-lg px-4 py-2.5">
                            <span class="material-icons text-base text-cerberus-accent">devices</span>
                            @if ($this->equiposAlcanzadosCount > 0)
                                Este plan aplicará a <strong class="text-gray-900 dark:text-white">{{ $this->equiposAlcanzadosCount }}</strong>
                                equipo(s) actualmente.
                            @else
                                No hay equipos activos con este alcance todavía.
                            @endif
                        </div>
                    @endif

                    @if (empty($equiposSeleccionados) && $this->equiposExcluidosCount > 0)
                        <div class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-400
                                    bg-amber-50 dark:bg-amber-500/10 rounded-lg px-4 py-2.5">
                            <span class="material-icons text-base">info</span>
                            {{ $this->equiposExcluidosCount }} equipo(s) de este alcance ya están en otro plan y no se cuentan aquí.
                        </div>
                    @endif

                    @if ($this->equiposDisponibles->isNotEmpty())
                        <div x-data="{ expandido: {{ ! empty($equiposSeleccionados) ? 'true' : 'false' }} }">
                            <button type="button" @click="expandido = ! expandido"
                                class="text-xs font-medium text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1">
                                <span class="material-icons text-sm" x-text="expandido ? 'expand_less' : 'expand_more'"></span>
                                Elegir equipos puntuales (opcional)
                            </button>
                            <p x-show="! expandido" class="text-xs text-gray-400 dark:text-cerberus-steel mt-1">
                                Por defecto el plan alcanza a todos los equipos de arriba. Ábrelo si en esta ronda solo quieres algunos puntuales.
                            </p>

                            <div x-show="expandido" x-cloak
                                 class="mt-2 max-h-48 overflow-y-auto space-y-1 border border-gray-200 dark:border-cerberus-steel/40 rounded-lg p-3">
                                @foreach ($this->equiposDisponibles as $eq)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-cerberus-light cursor-pointer">
                                        <input type="checkbox" wire:model="equiposSeleccionados" value="{{ $eq->id }}"
                                            class="rounded border-gray-300 dark:border-cerberus-steel text-cerberus-primary focus:ring-cerberus-primary">
                                        {{ $eq->codigo_interno }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($this->equiposExcluidosCount > 0)
                        <p class="text-xs text-gray-400 dark:text-cerberus-steel italic">
                            Todos los equipos de este alcance ya están en otro plan — no hay ninguno libre para elegir como puntual.
                        </p>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form.input
                            label="Frecuencia"
                            type="number"
                            wire:model="frecuencia_meses"
                            placeholder="Ej: 6"
                            suffix="meses"
                            :error="$errors->first('frecuencia_meses')"
                            required
                        />
                        <x-form.input
                            label="Próxima fecha"
                            type="date"
                            wire:model="fecha_proximo"
                            :error="$errors->first('fecha_proximo')"
                            required
                        />
                    </div>

                    <x-form.input
                        label="Duración estimada del lote"
                        type="number"
                        wire:model="duracion_dias_estimada"
                        placeholder="Ej: 5"
                        suffix="día(s)"
                        hint="Cuántos días le toma en total procesar todos los equipos de esta categoría (si son muchos, puede tomar más de un día)."
                        :error="$errors->first('duracion_dias_estimada')"
                        required
                    />

                    {{-- Checklist — la lista la posee Alpine (x-for), no Blade: así el DOM
                         nunca queda desincronizado de los índices tras agregar/quitar. --}}
                    <div x-data="{
                            items: @js(collect($checklist)->values()->all()),
                            nuevaTarea: '',
                            sync() { $wire.checklist = this.items; },
                            agregar() {
                                const t = this.nuevaTarea.trim();
                                if (! t) return;
                                this.items.push({ tarea: t, incluir: true });
                                this.nuevaTarea = '';
                                this.sync();
                            },
                            quitar(i) {
                                this.items.splice(i, 1);
                                this.sync();
                            },
                         }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-2">
                            Checklist que traerá cada caso generado
                        </label>
                        <div class="space-y-1.5">
                            <template x-for="(item, i) in items" :key="i">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" x-model="item.incluir" @change="sync()"
                                        class="peer flex-shrink-0 w-4 h-4 rounded border-gray-300 dark:border-cerberus-steel
                                               text-cerberus-primary focus:ring-cerberus-primary/30 cursor-pointer">
                                    <span class="text-sm flex-1 text-gray-700 dark:text-cerberus-light transition-colors
                                                 peer-checked:text-gray-400 dark:peer-checked:text-cerberus-steel peer-checked:line-through"
                                          x-text="item.tarea"></span>
                                    <button type="button" @click="quitar(i)"
                                        class="text-gray-400 hover:text-red-500 transition">
                                        <span class="material-icons text-sm">close</span>
                                    </button>
                                </div>
                            </template>
                            <p x-show="items.length === 0" class="text-xs text-gray-400 dark:text-cerberus-steel italic">
                                Sin tareas en el checklist.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 mt-3">
                            <input type="text" x-model="nuevaTarea"
                                @keydown.enter.prevent="agregar()"
                                placeholder="Agregar otra tarea..."
                                class="flex-1 rounded-lg px-3 py-1.5 text-sm
                                       bg-white dark:bg-cerberus-dark
                                       border border-gray-300 dark:border-cerberus-steel
                                       text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30">
                            <button type="button" @click="agregar()"
                                class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">
                                <span class="material-icons text-sm">add</span>
                            </button>
                        </div>
                    </div>

                    <x-form.select
                        searchable
                        label="Responsable"
                        placeholder="Sin asignar"
                        :options="$this->responsablesOpciones"
                        :selected="$responsable_id"
                        wire:model="responsable_id"
                        hint="Se asigna por defecto a cada caso que este plan genere."
                        :error="$errors->first('responsable_id')"
                    />

                    <x-form.textarea
                        label="Observaciones"
                        wire:model="observaciones"
                        rows="2"
                        placeholder="Notas sobre este plan..."
                        :error="$errors->first('observaciones')"
                    />

                    @if ($planId)
                        <div class="flex items-center gap-3 py-2">
                            <button type="button" wire:click="$toggle('activo')" role="switch"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full
                                       border-2 border-transparent transition-colors duration-200
                                       {{ $activo ? 'bg-cerberus-primary' : 'bg-gray-300 dark:bg-cerberus-steel/40' }}">
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white
                                             shadow ring-0 transition duration-200
                                             {{ $activo ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                            <span class="text-sm text-gray-600 dark:text-cerberus-light select-none">Plan activo</span>
                        </div>
                    @endif

                </div>

                <div class="flex justify-end gap-3 px-6 py-4 flex-shrink-0 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cancelar
                    </button>
                    <button wire:click="guardar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A]
                               text-white transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="guardar" class="material-icons text-sm">save</span>
                        <span wire:loading wire:target="guardar" class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading.remove wire:target="guardar">Guardar</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
