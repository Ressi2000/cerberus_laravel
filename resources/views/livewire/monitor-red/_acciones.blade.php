{{-- Menú de acciones de un host. Uso: @include('livewire.monitor-red._acciones', ['h' => $host]) --}}
<x-table.table-actions :model="$h" :editEvent="auth()->user()->can('update', $h) ? 'openMonitorHostEditar' : null">
    <x-slot name="acciones">
        @can('update', $h)
            <li>
                <button wire:click="alternarPausa({{ $h->id }})" @click="close()"
                        class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                               text-gray-600 dark:text-cerberus-light
                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                               hover:text-amber-600 dark:hover:text-amber-400 transition-colors duration-100">
                    <span class="material-icons text-base text-amber-500">{{ $h->pausado ? 'play_circle' : 'pause_circle' }}</span>
                    {{ $h->pausado ? 'Reanudar monitoreo' : 'Pausar (mantenimiento)' }}
                </button>
            </li>
        @endcan
        <li>
            <a href="{{ route('admin.monitor-red.caidas', ['host_id' => $h->id]) }}" @click="close()"
               class="flex items-center gap-3 px-4 py-2.5 w-full
                      text-gray-600 dark:text-cerberus-light
                      hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                      hover:text-[#1E40AF] dark:hover:text-white transition-colors duration-100">
                <span class="material-icons text-base text-cerberus-accent">history</span>
                Ver caídas
            </a>
        </li>
        @can('delete', $h)
            <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>
            <li>
                <button wire:click="$dispatch('confirmar', {
                            titulo: 'Quitar del monitoreo',
                            mensaje: '¿Quitar «{{ addslashes($h->nombre) }}» ({{ $h->host }}) del monitoreo? Su historial de caídas se conserva.',
                            accionEvento: 'monitorHostEliminarConfirmado',
                            accionParams: [[{{ $h->id }}]],
                            confirmLabel: 'Quitar',
                        })"
                        @click="close()"
                        class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                               text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-100">
                    <span class="material-icons text-base">delete</span>
                    Quitar del monitoreo
                </button>
            </li>
        @endcan
    </x-slot>
</x-table.table-actions>
