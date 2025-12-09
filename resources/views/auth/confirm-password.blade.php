<x-guest-layout>
    <x-auth-card>
        <div class="mb-4 text-sm text-gray-700 dark:text-gray-300">
            {{ __('Esta es un área segura de la aplicación. Por favor confirma tu contraseña antes de continuar.') }}
        </div>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div>
                <x-input-label for="password" :value="__('Contraseña')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="flex justify-end mt-6">
                <x-primary-button class="bg-[#1E40AF] hover:bg-[#1E3A8A] text-white dark:bg-[#A9D6E5] dark:text-[#0D1B2A] dark:hover:bg-[#89C2D9]">
                    {{ __('Confirmar') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
