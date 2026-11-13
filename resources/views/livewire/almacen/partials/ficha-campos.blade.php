{{-- Campos de la ficha técnica de una familia, ligados a $wire.valores.{id}. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
    @foreach ($familia->campos ?? [] as $campo)
        <div wire:key="ficha-{{ $campo['id'] }}">
            @if (($campo['tipo'] ?? '') === 'select')
                <x-form.select :label="$campo['nombre']" placeholder="Elige…"
                    :options="collect($campo['opciones'] ?? [])->mapWithKeys(fn ($o) => [$o => $o])"
                    wire:model.live="valores.{{ $campo['id'] }}"
                    :error="$errors->first('valores.' . $campo['id'])" required />
            @else
                <x-form.input :label="$campo['nombre']"
                    type="{{ in_array($campo['tipo'] ?? '', ['integer', 'decimal']) ? 'number' : 'text' }}"
                    wire:model.live.500ms="valores.{{ $campo['id'] }}"
                    :error="$errors->first('valores.' . $campo['id'])" required />
            @endif
        </div>
    @endforeach
</div>
