<div>
    @if ($open && $atributo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-md mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">tune</span>
                        Detalle del atributo
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Nombre</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $atributo->nombre }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Categoría</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $atributo->categoria->nombre }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Tipo</p>
                            <p class="text-sm text-gray-700 dark:text-cerberus-light mt-0.5 capitalize">{{ $atributo->tipo }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Orden</p>
                            <p class="text-sm text-gray-700 dark:text-cerberus-light mt-0.5">{{ $atributo->orden }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Valores en equipos</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $atributo->valores_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">Slug</p>
                            <p class="text-xs font-mono text-gray-500 dark:text-cerberus-steel mt-0.5">{{ $atributo->slug }}</p>
                        </div>
                    </div>

                    {{-- Toggles de comportamiento --}}
                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-100 dark:border-cerberus-steel/30">
                        @foreach([
                            ['requerido',        'Requerido'],
                            ['filtrable',         'Filtrable'],
                            ['visible_en_tabla',  'En tabla'],
                        ] as [$campo, $label])
                            <div class="text-center">
                                <span class="material-icons text-xl
                                    {{ $atributo->$campo
                                        ? 'text-green-500 dark:text-green-400'
                                        : 'text-gray-300 dark:text-cerberus-steel/40' }}">
                                    {{ $atributo->$campo ? 'check_circle' : 'cancel' }}
                                </span>
                                <p class="text-xs text-gray-500 dark:text-cerberus-light mt-0.5">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Opciones para tipo select --}}
                    @if ($atributo->tipo === 'select' && count($atributo->opciones ?? []) > 0)
                        <div class="pt-2 border-t border-gray-100 dark:border-cerberus-steel/30">
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide mb-2">
                                Opciones ({{ count($atributo->opciones) }})
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($atributo->opciones as $opcion)
                                    <span class="px-2 py-0.5 text-xs rounded-md
                                                 bg-gray-100 dark:bg-cerberus-dark
                                                 text-gray-700 dark:text-cerberus-light
                                                 border border-gray-200 dark:border-cerberus-steel/40">
                                        {{ $opcion }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="$dispatch('openAtributoEditar', { id: {{ $atributo->id }} }); close()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg font-medium
                               bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                               border border-amber-200 dark:border-amber-500/30
                               hover:bg-amber-100 dark:hover:bg-amber-500/20 transition">
                        <span class="material-icons text-base">edit</span> Editar
                    </button>
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>