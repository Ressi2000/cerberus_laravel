<div class="space-y-8">

    <div class="bg-white shadow rounded p-6">
        <h2 class="text-xl font-semibold">
            Histórico Técnico - {{ $equipo->codigo_interno }}
        </h2>

        <p class="text-sm text-gray-600">
            Categoría: {{ $equipo->categoria->nombre }}
        </p>
    </div>

    @foreach ($historial as $atributoId => $registros)
        <div class="bg-white shadow rounded p-6">

            <h3 class="text-lg font-semibold mb-4">
                {{ $registros->first()->atributo->nombre }}
            </h3>

            <div class="space-y-3">

                @foreach ($registros as $registro)
                    <div class="flex justify-between items-center border-b pb-2">

                        <div>
                            <span class="font-medium">
                                {{ $registro->valor }}
                            </span>

                            @if ($registro->es_actual)
                                <span class="ml-2 text-green-600 text-xs font-semibold">
                                    (Actual)
                                </span>
                            @endif
                        </div>

                        <div class="text-sm text-gray-500">
                            {{ $registro->created_at->format('d/m/Y H:i') }}
                        </div>

                        <div class="text-xs text-gray-400">
                            Por: {{ $registro->usuario?->name ?? 'Sistema' }}
                        </div>


                    </div>
                @endforeach

            </div>

        </div>
    @endforeach

</div>
