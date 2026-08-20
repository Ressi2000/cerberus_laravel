<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>
            <div class="relative z-50 w-full max-w-md mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-cerberus-accent">{{ $estadoId ? 'edit' : 'add_circle' }}</span>
                        {{ $estadoId ? 'Editar estado' : 'Nuevo estado' }}
                    </h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <x-form.input
                        label="Nombre"
                        wire:model="nombre"
                        placeholder="Ej: Disponible, En mantenimiento, Dado de baja..."
                        :error="$errors->first('nombre')"
                        required
                        hint="El nombre debe ser único. Se usará en el listado de equipos."
                    />

                    <div>
                        <div class="flex items-center gap-1.5 mb-1">
                            <label class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">
                                Color del badge
                                <span class="text-red-500 ml-0.5">*</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="color" wire:model="color"
                                class="h-10 w-14 rounded-lg border border-gray-300 dark:border-cerberus-steel
                                       bg-white dark:bg-cerberus-dark cursor-pointer p-1">

                            <input type="text" wire:model="color" maxlength="7"
                                placeholder="#64748B"
                                class="w-28 rounded-lg px-3 py-2 text-sm font-mono
                                       bg-white dark:bg-cerberus-dark
                                       border border-gray-300 dark:border-cerberus-steel
                                       text-[#1E293B] dark:text-white
                                       focus:outline-none focus:ring-2
                                       focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                                       dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary">

                            <span class="px-2 py-0.5 text-xs rounded-full font-medium border"
                                style="background-color: {{ $color }}1A; color: {{ $color }}; border-color: {{ $color }}4D;">
                                {{ $nombre ?: 'Vista previa' }}
                            </span>
                        </div>

                        {{-- Paleta de colores sugeridos --}}
                        <div class="flex items-center gap-1.5 mt-2">
                            @foreach (['#22C55E', '#3B82F6', '#F59E0B', '#F97316', '#EF4444', '#A855F7', '#64748B'] as $sugerido)
                                <button type="button" wire:click="$set('color', '{{ $sugerido }}')"
                                    class="w-5 h-5 rounded-full border border-black/10 dark:border-white/20 transition
                                           hover:scale-110 {{ strtoupper($color) === $sugerido ? 'ring-2 ring-offset-1 ring-[#1E40AF] dark:ring-cerberus-accent' : '' }}"
                                    style="background-color: {{ $sugerido }};">
                                </button>
                            @endforeach
                        </div>

                        @error('color')
                            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                <span class="material-icons text-xs">error_outline</span>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
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