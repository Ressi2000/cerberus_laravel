<x-guest-layout>
    <x-auth.auth-card>
        <x-form.errors />
        <h2 class="text-lg font-semibold mb-4 text-center">
            Selecciona la empresa
        </h2>

        <form method="POST" action="{{ route('empresa.select.store') }}">
            @csrf

            <x-form.select
                name="empresa_id"
                placeholder="Seleccione una empresa..."
                :options="$empresas->pluck('nombre', 'id')->toArray()"
                required
                searchable
            />

            <x-auth.primary-button class="mt-4 w-full">
                Continuar
            </x-primary-button>
        </form>
    </x-auth-card>
</x-guest-layout>
