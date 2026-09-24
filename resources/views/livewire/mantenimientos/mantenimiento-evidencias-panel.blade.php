<div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
        <span class="material-icons text-cerberus-accent text-base">photo_camera</span>
        Evidencia fotográfica
    </h3>

    @if ($this->mantenimiento->estaAbierto())
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <div class="min-w-[140px]">
                <x-form.select label="Momento" :options="['Antes' => 'Antes', 'Durante' => 'Durante', 'Después' => 'Después']" wire:model="tipoFoto" />
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">Foto</label>
                <input type="file" wire:model="foto" accept="image/*"
                    class="w-full text-sm text-gray-600 dark:text-cerberus-light
                           file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0
                           file:bg-cerberus-primary/10 file:text-cerberus-primary dark:file:text-cerberus-accent
                           file:text-xs file:font-medium">
                @error('foto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <button wire:click="subir" wire:loading.attr="disabled" wire:target="subir,foto"
                class="px-4 py-2 text-sm rounded-lg font-medium bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition disabled:opacity-60">
                Subir
            </button>
        </div>
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
