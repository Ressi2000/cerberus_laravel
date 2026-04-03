<div>
    @if ($open && $categoria)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-md mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel
                        rounded-xl shadow-xl">

                {{-- Cabecera --}}
                <div class="flex items-center justify-between px-6 py-4
                            border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">category</span>
                        Detalle de categoría
                    </h2>
                    <button wire:click="close"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="px-6 py-5 space-y-4">

                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                            Nombre
                        </p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mt-0.5">
                            {{ $categoria->nombre }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                            Descripción
                        </p>
                        <p class="text-sm text-gray-700 dark:text-cerberus-light mt-0.5">
                            {{ $categoria->descripcion ?? '—' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                                Asignable
                            </p>
                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-xs rounded-full
                                {{ $categoria->asignable
                                    ? 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30'
                                    : 'bg-gray-50 dark:bg-cerberus-steel/10 text-gray-500 dark:text-cerberus-steel border border-gray-200 dark:border-cerberus-steel/30'
                                }}">
                                <span class="material-icons text-xs">
                                    {{ $categoria->asignable ? 'check_circle' : 'remove_circle' }}
                                </span>
                                {{ $categoria->asignable ? 'Sí' : 'No' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                                Atributos
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $categoria->atributos_count }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                                Equipos
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $categoria->equipos_count }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="$dispatch('openCategoriaEditar', { id: {{ $categoria->id }} }); close()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg font-medium
                               bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                               border border-amber-200 dark:border-amber-500/30
                               hover:bg-amber-100 dark:hover:bg-amber-500/20 transition">
                        <span class="material-icons text-base">edit</span>
                        Editar
                    </button>
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg
                               bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white
                               hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>