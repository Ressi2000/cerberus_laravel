<div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
        <span class="material-icons text-cerberus-accent text-base">photo_camera</span>
        Evidencia fotográfica
    </h3>

    @if (! empty($this->momentosDisponibles))
        <div class="flex flex-wrap items-start gap-4 mb-4">
            <div class="min-w-[140px]">
                <x-form.select label="Momento" :options="$this->momentosDisponibles" wire:model="tipoFoto" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">Foto</label>
                <div class="flex items-center gap-3">
                    <label class="cursor-pointer flex items-center gap-2 px-4 py-2 rounded-lg text-sm
                                  bg-gray-100 dark:bg-cerberus-dark
                                  border border-gray-300 dark:border-cerberus-steel
                                  text-gray-700 dark:text-cerberus-light
                                  hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        <span class="material-icons text-base">upload</span>
                        Elegir foto
                        <input type="file" wire:model="foto" class="hidden" accept="image/*">
                    </label>

                    <div wire:loading wire:target="foto" class="text-xs text-gray-400 flex items-center gap-1">
                        <span class="material-icons text-sm animate-spin">refresh</span>
                        Subiendo...
                    </div>

                    @if ($foto)
                        <img src="{{ $foto->temporaryUrl() }}" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-cerberus-steel">
                    @endif
                </div>
                @error('foto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button wire:click="subir" wire:loading.attr="disabled" wire:target="subir"
                class="mt-6 px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition disabled:opacity-60">
                Subir
            </button>
        </div>
    @elseif ($this->mantenimiento->estaAbierto())
        <p class="text-xs text-gray-400 dark:text-cerberus-steel mb-4">
            Ya se registró la evidencia de este momento del caso.
        </p>
    @endif

    @if ($this->mantenimiento->evidencias->isEmpty())
        <p class="text-sm text-gray-400 dark:text-cerberus-steel text-center py-4">Sin fotos todavía.</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach ($this->mantenimiento->evidencias as $ev)
                <a wire:key="evidencia-{{ $ev->id }}" href="{{ $ev->url() }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-gray-200 dark:border-cerberus-steel/40">
                    <img src="{{ $ev->url() }}" class="w-full h-24 object-cover group-hover:opacity-80 transition">
                    @if ($ev->tipo)
                        <span class="absolute bottom-1 left-1 px-1.5 py-0.5 text-[10px] rounded bg-black/60 text-white">{{ $ev->tipo }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>
