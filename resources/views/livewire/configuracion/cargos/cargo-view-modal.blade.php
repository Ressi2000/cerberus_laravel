<div>
    @if ($open && $cargo)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-md mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                {{-- Cabecera --}}
                <div class="flex items-center justify-between px-6 py-4
                            border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">work</span>
                        Detalle de cargo
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
                            {{ $cargo->nombre }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                            Departamento
                        </p>
                        <p class="text-sm text-gray-800 dark:text-white mt-0.5">
                            {{ $cargo->departamento?->nombre ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                            Empresa
                        </p>
                        @if ($cargo->empresa)
                            <p class="text-sm text-gray-800 dark:text-white mt-0.5">
                                {{ $cargo->empresa->nombre }}
                            </p>
                        @else
                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-xs rounded-full
                                         bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400
                                         border border-green-200 dark:border-green-500/30">
                                <span class="material-icons text-xs">public</span>
                                Global — visible en todas las empresas
                            </span>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-cerberus-accent uppercase tracking-wide">
                            Usuarios con este cargo
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">
                            {{ $cargo->usuarios_count }}
                        </p>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4
                            border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="$dispatch('openCargoEditar', { id: {{ $cargo->id }} }); close()"
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