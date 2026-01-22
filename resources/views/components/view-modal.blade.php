@props([
    'id',
    'title' => 'Detalle',
    'data', // Modelo User u otro modelo
])

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden flex items-center justify-center">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>

    <!-- Modal -->
    <div class="relative z-50 w-full max-w-3xl p-6 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus">

        {{-- Título --}}
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                <span class="material-icons text-blue-400">info</span>
                {{ $title }}
            </h2>

            <button data-modal-hide="{{ $id }}" class="text-gray-300 hover:text-white">
                <span class="material-icons">close</span>
            </button>
        </div>

        {{-- CONTENIDO --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- FOTO --}}
            <div class="flex flex-col items-center">
                <img src="{{ $data->foto ? asset('storage/' . $data->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($data->name) }}"
                     class="w-28 h-28 rounded-full object-cover border border-cerberus-steel">

                <h3 class="text-white text-lg font-semibold mt-3">{{ $data->name }}</h3>
                <p class="text-cerberus-light text-sm">{{ $data->email }}</p>

                <span class="mt-2 px-3 py-1 rounded-full text-xs
                    {{ $data->estado === 'Activo' ? 'bg-green-700 text-green-200' : 'bg-red-700 text-red-200' }}">
                    {{ $data->estado }}
                </span>
            </div>

            {{-- INFORMACIÓN --}}
            <div class="lg:col-span-2 space-y-4">

                <div class="bg-cerberus-dark border border-cerberus-steel p-4 rounded-xl">
                    <h4 class="text-cerberus-accent font-semibold mb-2">Información Personal</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
                        <p><span class="font-semibold text-white">Cédula:</span> {{ $data->cedula }}</p>
                        <p><span class="font-semibold text-white">Teléfono:</span> {{ $data->telefono }}</p>
                        <p><span class="font-semibold text-white">Ficha:</span> {{ $data->ficha }}</p>
                        <p><span class="font-semibold text-white">Rol:</span> {{ $data->getRoleNames()->join(', ') ?: '—' }}</p>
                    </div>
                </div>

                <div class="bg-cerberus-dark border border-cerberus-steel p-4 rounded-xl">
                    <h4 class="text-cerberus-accent font-semibold mb-2">Información Laboral</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
                        <p><span class="font-semibold text-white">Empresa:</span> {{ $data->empresaNomina->nombre ?? '—' }}</p>
                        <p><span class="font-semibold text-white">Departamento:</span> {{ $data->departamento->nombre ?? '—' }}</p>
                        <p><span class="font-semibold text-white">Cargo:</span> {{ $data->cargo->nombre ?? '—' }}</p>
                        <p><span class="font-semibold text-white">Ubicación:</span> {{ $data->ubicacion->nombre ?? '—' }}</p>
                    </div>
                </div>

                <div class="bg-cerberus-dark border border-cerberus-steel p-4 rounded-xl">
                    <h4 class="text-cerberus-accent font-semibold mb-2">Registro</h4>

                    <p class="text-sm text-cerberus-light">
                        <span class="font-semibold text-white">Creado:</span>
                        {{ $data->created_at?->format('d/m/Y H:i') }}
                    </p>
                </div>

            </div>
        </div>

        {{-- BOTÓN CERRAR --}}
        <div class="flex justify-end mt-6">
            <button data-modal-hide="{{ $id }}"
                class="px-5 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                Cerrar
            </button>
        </div>

    </div>
</div>
