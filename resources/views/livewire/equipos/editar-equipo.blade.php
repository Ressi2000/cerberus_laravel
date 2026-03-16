<div class="space-y-6">

    <div>
        <label>Categoría</label>
        <input type="text"
               value="{{ $equipo->categoria->nombre }}"
               disabled
               class="form-control bg-gray-100">
    </div>

    <div>
        <label>Estado</label>
        <select wire:model="estado_id" class="form-control">
            @foreach(\App\Models\EstadoEquipo::all() as $estado)
                <option value="{{ $estado->id }}">
                    {{ $estado->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <hr>

    <h4>Características Técnicas</h4>

    @foreach($atributos as $atributo)

        <div class="mb-3">
            <label>{{ $atributo->nombre }}</label>

            @switch($atributo->tipo)

                @case('boolean')
                    <input type="checkbox"
                           wire:model="valores.{{ $atributo->id }}">
                    @break

                @case('date')
                    <input type="date"
                           wire:model="valores.{{ $atributo->id }}"
                           class="form-control">
                    @break

                @default
                    <input type="text"
                           wire:model="valores.{{ $atributo->id }}"
                           class="form-control">
            @endswitch

            @error("valores.{$atributo->id}")
                <span class="text-red-600 text-sm">
                    {{ $message }}
                </span>
            @enderror
        </div>

    @endforeach

    <button wire:click="actualizar"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Actualizar Equipo
    </button>

</div>
