<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">playlist_add</span>
                        Importar varios hosts
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-3">
                    @if ($this->empresasOpciones->count() > 1)
                        <x-form.select label="Empresa" placeholder="Selecciona una empresa" :options="$this->empresasOpciones"
                            wire:model="empresa_id" :error="$errors->first('empresa_id')" required />
                    @endif

                    <p class="text-sm text-gray-600 dark:text-cerberus-light">
                        Una línea por host: <code class="px-1 rounded bg-gray-100 dark:bg-cerberus-dark">ip, nombre, tipo, proveedor, puerto</code>.
                        Solo la IP es obligatoria. También se puede pegar directo desde Excel.
                    </p>

                    <x-form.textarea wire:model="texto" rows="8"
                        placeholder="192.168.1.1, Router principal, Router, ISP 1, 80" />
                    @error('texto') <p class="text-sm text-red-500 -mt-2">{{ $message }}</p> @enderror

                    @if ($fallos)
                        <div class="rounded-lg border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3">
                            <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1">
                                Importados: {{ $importados }}. Líneas no importadas ({{ count($fallos) }}):
                            </p>
                            <ul class="text-xs text-amber-700 dark:text-amber-300 font-mono max-h-32 overflow-y-auto space-y-0.5">
                                @foreach ($fallos as $f)
                                    <li>{{ $f }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel">
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        {{ $fallos ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    <button wire:click="importar" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A]
                               text-white transition flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="importar" class="material-icons text-sm">upload</span>
                        <span wire:loading wire:target="importar" class="material-icons text-sm animate-spin">refresh</span>
                        Importar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
