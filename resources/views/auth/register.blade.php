<x-guest-layout>
    <x-auth.auth-card>
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <x-auth.input-label for="name" :value="__('Nombre completo')" />
                <x-auth.text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-auth.input-error :messages="$errors->get('name')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="mt-4">
                <x-auth.input-label for="email" :value="__('Correo electrónico')" />
                <x-auth.text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-auth.input-error :messages="$errors->get('email')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="mt-4">
                <x-auth.input-label for="password" :value="__('Contraseña')" />
                <x-auth.text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <x-auth.input-error :messages="$errors->get('password')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="mt-4">
                <x-auth.input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                <x-auth.text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-auth.input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <a class="underline text-sm text-blue-600 dark:text-blue-300 hover:text-blue-800 dark:hover:text-blue-400 transition" href="{{ route('login') }}">
                    {{ __('¿Ya tienes cuenta?') }}
                </a>

                <x-auth.primary-button class="ms-3 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white dark:bg-[#A9D6E5] dark:text-[#0D1B2A] dark:hover:bg-[#89C2D9]">
                    {{ __('Registrarse') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
