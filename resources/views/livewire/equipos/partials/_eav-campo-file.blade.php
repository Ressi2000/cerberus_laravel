{{--
    Partial: _eav-campo-file.blade.php
    Uso: @include('livewire.equipos.partials._eav-campo-file', ['atributo' => $atributo])

    Variables esperadas:
      $atributo        array  → la fila del EAV ['id', 'nombre', 'tipo', 'requerido']
      $archivoActual   string|null → path actual en storage (solo en editar, null en crear)
      $modo            string → 'crear' | 'editar'
--}}

@php
    $atributoId   = $atributo['id'];
    $nombre       = $atributo['nombre'];
    $requerido    = $atributo['requerido'];
    $tieneActual  = ! empty($archivoActual);
    $nombreArchivo = $tieneActual ? basename($archivoActual) : null;
    $urlArchivo    = $tieneActual ? Storage::url($archivoActual) : null;

    // Detectar si el archivo actual es una imagen (para mostrar preview)
    $esImagen = $tieneActual && preg_match('/\.(jpg|jpeg|png|webp)$/i', $archivoActual);
@endphp

<div class="space-y-2" wire:key="file-{{ $atributoId }}">

    {{-- Label --}}
    <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent">
        {{ $nombre }}
        @if($requerido)
            <span class="text-red-400">*</span>
        @endif
    </label>

    {{-- Archivo actual (solo en editar) --}}
    @if($tieneActual)
        <div class="flex items-center gap-3 p-3 rounded-lg
                    bg-gray-50 dark:bg-cerberus-dark
                    border border-gray-200 dark:border-cerberus-steel/60">

            @if($esImagen)
                <img src="{{ $urlArchivo }}"
                     alt="{{ $nombreArchivo }}"
                     class="h-10 w-10 rounded object-cover border border-gray-200 dark:border-cerberus-steel flex-shrink-0">
            @else
                <span class="material-icons text-cerberus-accent text-2xl flex-shrink-0">description</span>
            @endif

            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-700 dark:text-white truncate">
                    {{ $nombreArchivo }}
                </p>
                <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">
                    Archivo actual · reemplazar subiendo uno nuevo
                </p>
            </div>

            <a href="{{ $urlArchivo }}"
               target="_blank"
               class="flex-shrink-0 flex items-center gap-1 px-2 py-1 rounded
                      text-xs text-cerberus-accent hover:text-white
                      bg-cerberus-primary/10 hover:bg-cerberus-primary/30
                      transition">
                <span class="material-icons text-sm">open_in_new</span>
                Ver
            </a>
        </div>
    @endif

    {{-- Input de subida --}}
    <div class="relative">
        <label class="flex flex-col items-center justify-center w-full h-24 px-4
                      border-2 border-dashed rounded-lg cursor-pointer transition
                      border-gray-300 dark:border-cerberus-steel
                      bg-gray-50 dark:bg-cerberus-dark
                      hover:border-cerberus-primary hover:bg-cerberus-primary/5
                      @error('archivos.'.$atributoId) border-red-400 @enderror">

            {{-- Indicador de carga --}}
            <div wire:loading wire:target="archivos.{{ $atributoId }}"
                 class="flex flex-col items-center gap-1 text-cerberus-accent">
                <span class="material-icons animate-spin text-xl">refresh</span>
                <span class="text-xs">Subiendo...</span>
            </div>

            {{-- Preview del archivo seleccionado (antes de guardar) --}}
            <div wire:loading.remove wire:target="archivos.{{ $atributoId }}">
                @if(isset($archivos[$atributoId]) && $archivos[$atributoId])
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-green-500 text-xl">check_circle</span>
                        <div>
                            <p class="text-xs font-medium text-gray-700 dark:text-white">
                                {{ $archivos[$atributoId]->getClientOriginalName() }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                                {{ number_format($archivos[$atributoId]->getSize() / 1024, 1) }} KB
                                · Listo para guardar
                            </p>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center gap-1 text-gray-400 dark:text-cerberus-steel">
                        <span class="material-icons text-xl">upload_file</span>
                        <p class="text-xs text-center">
                            <span class="text-cerberus-accent font-medium">Haz clic para subir</span>
                            {{ $tieneActual ? 'un nuevo archivo' : 'el archivo' }}
                        </p>
                        <p class="text-xs">PDF, imagen, Word, Excel · máx. 10 MB</p>
                    </div>
                @endif
            </div>

            <input type="file"
                   wire:model="archivos.{{ $atributoId }}"
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
        </label>
    </div>

    {{-- Errores de validación --}}
    @error('archivos.'.$atributoId)
        <p class="text-red-400 text-xs mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error</span>
            {{ $message }}
        </p>
    @enderror

</div>