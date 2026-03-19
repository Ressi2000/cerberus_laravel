<x-guest-layout>
    <x-auth.auth-card>
        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-auth.input-label for="email" :value="__('Correo electrónico')" />
                <x-auth.text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-auth.input-error :messages="$errors->get('email')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="mt-4">
                <x-auth.input-label for="username" :value="__('Usuario')" />
                <x-auth.text-input id="username" name="username" :value="old('username')" required autocomplete="username" />
                <x-auth.input-error :messages="$errors->get('username')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="mt-4">
                <x-auth.input-label for="password" :value="__('Nueva contraseña')" />
                <x-auth.text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <x-auth.input-error :messages="$errors->get('password')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="mt-4">
                <x-auth.input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                <x-auth.text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-auth.input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <div class="flex items-center justify-end mt-6">
                <x-auth.primary-button class="bg-[#1E40AF] hover:bg-[#1E3A8A] text-white dark:bg-[#A9D6E5] dark:text-[#0D1B2A] dark:hover:bg-[#89C2D9]">
                    {{ __('Restablecer contraseña') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
