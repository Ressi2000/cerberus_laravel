<div>
    @if ($open && $user)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div
                class="relative z-50 w-full max-w-3xl p-6 bg-cerberus-mid border border-cerberus-steel rounded-xl shadow-cerberus">

                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                        <span class="material-icons text-blue-400">info</span>
                        Detalle del Usuario
                    </h2>
                    <button wire:click="close" class="text-gray-300 hover:text-white">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- FOTO --}}
                    <div class="flex flex-col items-center">
                        <img src="{{ $user->foto_url }}"
                            class="w-28 h-28 rounded-full object-cover border border-cerberus-steel">

                        <h3 class="text-white text-lg font-semibold mt-3">{{ $user->name }}</h3>
                        <p class="text-cerberus-light text-sm">{{ $user->email }}</p>

                        <span
                            class="mt-2 px-3 py-1 rounded-full text-xs
                    {{ $user->estado === 'Activo' ? 'bg-green-700 text-green-200' : 'bg-red-700 text-red-200' }}">
                            {{ $user->estado }}
                        </span>
                    </div>

                    {{-- INFORMACIÓN --}}
                    <div class="lg:col-span-2 space-y-4">

                        <div class="bg-cerberus-dark border border-cerberus-steel p-4 rounded-xl">
                            <h4 class="text-cerberus-accent font-semibold mb-2">Información Personal</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
                                <p><span class="font-semibold text-white">Cédula:</span> {{ $user->cedula }}</p>
                                <p><span class="font-semibold text-white">Teléfono:</span> {{ $user->telefono }}</p>
                                <p><span class="font-semibold text-white">Ficha:</span> {{ $user->ficha }}</p>
                                <p><span class="font-semibold text-white">Rol:</span>
                                    {{ $user->getRoleNames()->join(', ') ?: '—' }}</p>
                            </div>
                        </div>

                        <div class="bg-cerberus-dark border border-cerberus-steel p-4 rounded-xl">
                            <h4 class="text-cerberus-accent font-semibold mb-2">Información Laboral</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-cerberus-light">
                                <p><span class="font-semibold text-white">Empresa:</span>
                                    {{ $user->empresaNomina->nombre ?? '—' }}</p>
                                <p><span class="font-semibold text-white">Departamento:</span>
                                    {{ $user->departamento->nombre ?? '—' }}</p>
                                <p><span class="font-semibold text-white">Cargo:</span>
                                    {{ $user->cargo->nombre ?? '—' }}</p>
                                <p><span class="font-semibold text-white">Ubicación:</span>
                                    {{ $user->ubicacion->nombre ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="bg-cerberus-dark border border-cerberus-steel p-4 rounded-xl">
                            <h4 class="text-cerberus-accent font-semibold mb-2">Registro</h4>

                            <p class="text-sm text-cerberus-light">
                                <span class="font-semibold text-white">Creado:</span>
                                {{ $user->created_at?->format('d/m/Y H:i') }}
                            </p>
                        </div>

                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button wire:click="close" class="px-5 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
