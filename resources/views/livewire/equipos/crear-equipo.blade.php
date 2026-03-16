<div class="space-y-6">

    <div>
        <label>Categoría</label>
        <select wire:model.live="categoria_id" class="form-control">
            <option value="">Seleccione</option>
            @foreach($categorias as $categoria)
                <option value="{{ $categoria->id }}">
                    {{ $categoria->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label>Código Interno</label>
        <input type="text" wire:model="codigo_interno" class="form-control">
    </div>

    <div>
        <label>Serial</label>
        <input type="text" wire:model="serial" class="form-control">
    </div>

    @if($atributos)

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
    @endif

    <button wire:click="guardar"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Guardar Equipo
    </button>

</div>

