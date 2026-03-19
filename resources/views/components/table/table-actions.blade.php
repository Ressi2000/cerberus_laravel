@props([
    'editUrl'     => null,
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

<div class="relative" x-data="{ open: false }" @click.outside="open = false">

    <button @click="open = !open"
        class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-cerberus-steel text-gray-300 transition-colors">
        <span class="material-icons">more_vert</span>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 z-50 mt-1 bg-cerberus-mid border border-cerberus-steel
                text-white rounded-lg shadow-cerberus w-44"
         style="display: none;">

        <ul class="py-2 text-sm">

            {{-- Ver --}}
            @if($viewEvent && $id)
                <li>
                    <button
                        @click="open = false"
                        wire:click="$dispatch('{{ $viewEvent }}', { id: {{ $id }} })"
                        class="flex items-center gap-2 px-4 py-2 w-full text-left
                               hover:bg-cerberus-dark text-cerberus-light hover:text-white transition-colors">
                        <span class="material-icons text-base text-blue-400">visibility</span>
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
                                @click="open = false"
                                class="flex items-center gap-2 px-4 py-2
                                       hover:bg-cerberus-dark text-cerberus-light hover:text-white transition-colors">
                                <span class="material-icons text-base text-yellow-400">edit</span>
                                Editar
                            </a>
                        </li>
                    @endcan
                @else
                    <li>
                        <a href="{{ $editUrl }}"
                            @click="open = false"
                            class="flex items-center gap-2 px-4 py-2
                                   hover:bg-cerberus-dark text-cerberus-light hover:text-white transition-colors">
                            <span class="material-icons text-base text-yellow-400">edit</span>
                            Editar
                        </a>
                    </li>
                @endif
            @endif

            {{-- Eliminar --}}
            @if($deleteEvent && $id)
                @if($policy)
                    @can('delete', $policy)
                        <li>
                            <button
                                @click="open = false"
                                wire:click="$dispatch('{{ $deleteEvent }}', { id: {{ $id }} })"
                                class="flex items-center gap-2 px-4 py-2 w-full text-left
                                       hover:bg-red-600/20 text-cerberus-light hover:text-red-300 transition-colors">
                                <span class="material-icons text-base text-red-400">delete</span>
                                {{ $deleteLabel }}
                            </button>
                        </li>
                    @endcan
                @else
                    <li>
                        <button
                            @click="open = false"
                            wire:click="$dispatch('{{ $deleteEvent }}', { id: {{ $id }} })"
                            class="flex items-center gap-2 px-4 py-2 w-full text-left
                                   hover:bg-red-600/20 text-cerberus-light hover:text-red-300 transition-colors">
                            <span class="material-icons text-base text-red-400">delete</span>
                            {{ $deleteLabel }}
                        </button>
                    </li>
                @endif
            @endif

        </ul>
    </div>
</div>
