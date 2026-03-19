<x-guest-layout>
    <x-auth.auth-card>
        <x-form.errors />
        <h2 class="text-lg font-semibold mb-4 text-center">
            Selecciona la empresa
        </h2>

        <form method="POST" action="{{ route('empresa.select.store') }}">
            @csrf

            <select name="empresa_id" required class="w-full border rounded px-3 py-2">
                @foreach ($empresas as $empresa)
                    <option value="{{ $empresa->id }}">
                        {{ $empresa->nombre }}
                    </option>
                @endforeach
            </select>

            <x-auth.primary-button class="mt-4 w-full">
                Continuar
            </x-primary-button>
        </form>
    </x-auth-card>
</x-guest-layout>
