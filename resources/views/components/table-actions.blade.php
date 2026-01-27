@props([
    'editUrl' => null,
    'deleteModalId' => null,
    'viewModalId' => null,
    'user' => null,
])

<div class="relative">
    <button data-dropdown-toggle="dropdown-{{ $attributes->get('row-id') }}"
        class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-cerberus-steel text-gray-300">
        <span class="material-icons">more_vert</span>
    </button>

    <div id="dropdown-{{ $attributes->get('row-id') }}"
        class="z-50 hidden bg-cerberus-mid border border-cerberus-steel text-white rounded-lg shadow-cerberus w-44">

        <ul class="py-2 text-sm">

            @if ($viewModalId)
                <li>
                    <button type="button"
                        data-modal-target="{{ $viewModalId }}"
                        data-modal-toggle="{{ $viewModalId }}"
                        class="flex items-center gap-2 px-4 py-2 w-full text-left hover:bg-cerberus-dark">
                        <span class="material-icons text-base">visibility</span>
                        Ver
                    </button>
                </li>
            @endif
            
            @can('update', $user)
            @if ($editUrl)
                <li>
                    <a href="{{ $editUrl }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-cerberus-dark">
                        <span class="material-icons text-base">edit</span>
                        Editar
                    </a>
                </li>
            @endif
            @endcan
            
            {{-- Eliminar con modal --}}
            @can('delete', $user)
                @if ($deleteModalId)
                    <li>
                        <button type="button"
                            data-modal-target="{{ $deleteModalId }}"
                            data-modal-toggle="{{ $deleteModalId }}"
                            class="flex items-center gap-2 px-4 py-2 w-full text-left hover:bg-red-600 hover:text-white">
                            <span class="material-icons text-base">delete</span>
                            Inactivar
                        </button>
                    </li>
                @endif
            @endcan
        </ul>
    </div>
</div>
