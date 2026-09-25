<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-lg mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $planId ? 'edit' : 'event_repeat' }}</span>
                        {{ $planId ? 'Editar plan de mantenimiento' : 'Nuevo plan de mantenimiento' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

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
                            wire:model.live="categoria_id"
                            :error="$errors->first('categoria_id')"
                            :disabled="! $empresa_id || $planId"
                            hint="El plan aplica a TODOS los equipos activos de esta categoría, en esta empresa."
                            required
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
                                No hay equipos activos de esta categoría en esta empresa todavía.
                            @endif
                        </div>
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

                    {{-- Checklist --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-2">
                            Checklist que traerá cada caso generado
                        </label>
                        <div class="space-y-1.5">
                            @foreach ($checklist as $i => $item)
                                <div wire:key="plan-checklist-{{ $i }}" class="flex items-center gap-2">
                                    <button type="button" wire:click="$set('checklist.{{ $i }}.incluir', {{ $item['incluir'] ? 'false' : 'true' }})"
                                        class="flex-shrink-0 w-5 h-5 rounded border flex items-center justify-center transition
                                               {{ $item['incluir']
                                                   ? 'bg-cerberus-primary border-cerberus-primary text-white'
                                                   : 'border-gray-300 dark:border-cerberus-steel' }}">
                                        @if ($item['incluir'])
                                            <span class="material-icons text-xs">check</span>
                                        @endif
                                    </button>
                                    <span class="text-sm flex-1 {{ $item['incluir'] ? 'text-gray-700 dark:text-cerberus-light' : 'text-gray-400 dark:text-cerberus-steel line-through' }}">
                                        {{ $item['tarea'] }}
                                    </span>
                                    <button type="button" wire:click="quitarTareaChecklist({{ $i }})" class="text-gray-400 hover:text-red-500 transition">
                                        <span class="material-icons text-sm">close</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-2 mt-3">
                            <input type="text" wire:model="nuevaTareaChecklist" wire:keydown.enter.prevent="agregarTareaChecklist"
                                placeholder="Agregar otra tarea..."
                                class="flex-1 rounded-lg px-3 py-1.5 text-sm
                                       bg-white dark:bg-cerberus-dark
                                       border border-gray-300 dark:border-cerberus-steel
                                       text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30">
                            <button type="button" wire:click="agregarTareaChecklist"
                                class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white">
                                <span class="material-icons text-sm">add</span>
                            </button>
                        </div>
                    </div>

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

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
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
