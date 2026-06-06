<x-guest-layout>
    <x-auth.auth-card>
        <x-form.errors />
        <h2 class="text-lg font-semibold mb-4 text-center">
            Selecciona la empresa
        </h2>

        <form method="POST" action="{{ route('empresa.select.store') }}">
            @csrf

            <select name="empresa_id" required
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2
                       bg-white dark:bg-[#1B263B]
                       text-gray-900 dark:text-gray-100
                       focus:outline-none focus:ring-2 focus:ring-[#1E40AF] dark:focus:ring-[#A9D6E5]
                       transition">
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
