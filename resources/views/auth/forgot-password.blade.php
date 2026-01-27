<x-guest-layout>
    <x-auth-card>
        <div class="mb-4 text-sm text-gray-700 dark:text-gray-300">
            {{ __('¿Olvidaste tu contraseña? No hay problema. Ingresa tu correo electrónico y te enviaremos un enlace para restablecerla.') }}
        </div>

        <x-auth-session-status class="mb-4 text-center text-blue-600 dark:text-blue-300" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div>
                <x-input-label for="username" :value="__('Usuario')" />
                <x-text-input id="username" name="username" :value="old('username')" required autofocus />
                <x-input-error :messages="$errors->get('username')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="mt-4">
                <x-input-label for="email" :value="__('Correo electrónico')" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="flex items-center justify-end mt-6">
                <x-primary-button
                    class="bg-[#1E40AF] hover:bg-[#1E3A8A] text-white dark:bg-[#A9D6E5] dark:text-[#0D1B2A] dark:hover:bg-[#89C2D9]">
                    {{ __('Enviar enlace de restablecimiento') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
