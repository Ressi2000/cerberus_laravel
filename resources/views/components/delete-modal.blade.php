@props([
    'id',
    'title' => 'Confirmar eliminación',
    'message' => '¿Seguro que deseas eliminar este registro?',
    'action',
])

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden flex items-center justify-center">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>

    <!-- Modal -->
    <div class="relative z-50 w-full max-w-md p-6 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus">

        <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
            <span class="material-icons text-red-400">warning</span>
            {{ $title }}
        </h2>

        <p class="text-cerberus-light mb-6">{{ $message }}</p>

        <form method="POST" action="{{ $action }}">
            @csrf
            @method('DELETE')

            <div class="flex justify-end space-x-3">
                <button type="button" data-modal-hide="{{ $id }}"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                    Cancelar
                </button>

                <button type="submit"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center gap-1">
                    <span class="material-icons text-sm">delete</span>
                    Eliminar
                </button>
            </div>
        </form>
    </div>
</div>
