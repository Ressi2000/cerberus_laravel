@props([
    'editUrl'     => null,
    'editEvent'   => null,
    'model'       => null,
    'modelId'     => null,
    'viewEvent'   => null,
    'deleteEvent' => null,
    'deleteLabel' => 'Eliminar',
    'policy'      => null,
    'rowId'       => null,
])

@php
    $id     = $modelId ?? $model?->id;
    $rowKey = $rowId ?? $id;
@endphp

{{--
    El dropdown usa posición fija calculada por JS para que nunca
    quede recortado por el overflow de la tabla.
--}}
<div class="relative flex justify-center"
     x-data="tableActions()"
     @click.outside="close()">

    {{-- Botón de tres puntos --}}
    <button @click.stop="toggle($el)"
        class="flex items-center justify-center w-8 h-8 rounded-lg
               text-gray-400 dark:text-cerberus-steel
               hover:bg-gray-100 dark:hover:bg-cerberus-steel/30
               hover:text-gray-700 dark:hover:text-white
               transition-all duration-150"
        :class="{ 'bg-gray-100 dark:bg-cerberus-steel/30 text-gray-700 dark:text-white': open }">
        <span class="material-icons text-lg">more_vert</span>
    </button>

    {{--
        Dropdown con posición fija (via teleport o posicionamiento manual).
        Se usa posición absoluta con z-[9999] para evitar clip del overflow-x de la tabla.
    --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-[9999] w-44
                bg-white dark:bg-cerberus-mid
                border border-gray-100 dark:border-cerberus-steel/60
                rounded-xl shadow-xl dark:shadow-black/30
                overflow-hidden"
         :style="`top: ${dropdownY}px; left: ${dropdownX}px;`"
         style="display:none; transform-origin: top right;">

        <ul class="py-1 text-sm">

            {{-- Ver detalle --}}
            @if($viewEvent && $id)
                <li>
                    <button
                        @click="close()"
                        wire:click="$dispatch('{{ $viewEvent }}', { id: {{ $id }} })"
                        class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                               text-gray-600 dark:text-cerberus-light
                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                               hover:text-[#1E40AF] dark:hover:text-white
                               transition-colors duration-100">
                        <span class="material-icons text-base text-[#1E40AF]/70 dark:text-cerberus-accent">
                            visibility
                        </span>
                        Ver detalle
                    </button>
                </li>
            @endif

            {{-- Editar --}}
            @if($editUrl)
                @if($policy)
                    @can('update', $policy)
                        <li>
                            <a href="{{ $editUrl }}"
                               @click="close()"
                               class="flex items-center gap-3 px-4 py-2.5
                                      text-gray-600 dark:text-cerberus-light
                                      hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                      hover:text-amber-600 dark:hover:text-amber-400
                                      transition-colors duration-100">
                                <span class="material-icons text-base text-amber-500">edit</span>
                                Editar
                            </a>
                        </li>
                    @endcan
                @else
                    <li>
                        <a href="{{ $editUrl }}"
                           @click="close()"
                           class="flex items-center gap-3 px-4 py-2.5
                                  text-gray-600 dark:text-cerberus-light
                                  hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                  hover:text-amber-600 dark:hover:text-amber-400
                                  transition-colors duration-100">
                            <span class="material-icons text-base text-amber-500">edit</span>
                            Editar
                        </a>
                    </li>
                @endif
            @endif

            {{-- Editar por evento (modal) ← NUEVO --}}
            @if($editEvent && $id)
                <li>
                    <button @click="close()"
                        wire:click="$dispatch('{{ $editEvent }}', { id: {{ $id }} })"
                        class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                               text-gray-600 dark:text-cerberus-light
                               hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                               hover:text-amber-600 dark:hover:text-amber-400
                               transition-colors duration-100">
                        <span class="material-icons text-base text-amber-500">edit</span>
                        Editar
                    </button>
                </li>
            @endif

            {{-- Separador antes de eliminar --}}
            @if($deleteEvent && $id)
                <li>
                    <div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div>
                </li>
            @endif

            {{-- Eliminar / Dar de baja --}}
            @if($deleteEvent && $id)
                @if($policy)
                    @can('delete', $policy)
                        <li>
                            <button
                                @click="close()"
                                wire:click="$dispatch('{{ $deleteEvent }}', { id: {{ $id }} })"
                                class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                       text-red-600 dark:text-red-400
                                       hover:bg-red-50 dark:hover:bg-red-500/10
                                       transition-colors duration-100">
                                <span class="material-icons text-base">delete</span>
                                {{ $deleteLabel }}
                            </button>
                        </li>
                    @endcan
                @else
                    <li>
                        <button
                            @click="close()"
                            wire:click="$dispatch('{{ $deleteEvent }}', { id: {{ $id }} })"
                            class="flex items-center gap-3 px-4 py-2.5 w-full text-left
                                   text-red-600 dark:text-red-400
                                   hover:bg-red-50 dark:hover:bg-red-500/10
                                   transition-colors duration-100">
                            <span class="material-icons text-base">delete</span>
                            {{ $deleteLabel }}
                        </button>
                    </li>
                @endif
            @endif

        </ul>
    </div>
</div>

@once
<script>
function tableActions() {
    return {
        open: false,
        dropdownX: 0,
        dropdownY: 0,

        toggle(triggerEl) {
            if (this.open) {
                this.close()
                return
            }

            // Calcular posición relativa al viewport
            const rect = triggerEl.getBoundingClientRect()
            const dropdownH = 160  // altura aproximada del dropdown
            const dropdownW = 176  // w-44 = 11rem = 176px

            // Abrir hacia abajo o hacia arriba según espacio disponible
            const spaceBelow = window.innerHeight - rect.bottom
            const openUp = spaceBelow < dropdownH + 16

            this.dropdownX = Math.max(8, rect.right - dropdownW)
            this.dropdownY = openUp
                ? rect.top - dropdownH - 4
                : rect.bottom + 4

            this.open = true
        },

        close() {
            this.open = false
        }
    }
}
</script>
@endonce