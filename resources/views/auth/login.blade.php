<x-guest-layout>
    <x-auth.auth-card>
        <x-auth.auth-session-status class="mb-4 text-center text-blue-600 dark:text-blue-300" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            {{-- <div>
                <x-auth.input-label for="email" :value="__('Correo electrónico')" />
                <x-auth.text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-auth.input-error :messages="$errors->get('email')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div> --}}

            <!-- Username -->
            <div>
                <x-auth.input-label for="username" :value="__('Usuario')" />
                <x-auth.text-input id="username" type="text" name="username" :value="old('username')" required autofocus
                    autocomplete="username" />
                <x-auth.input-error :messages="$errors->get('username')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>


            <!-- Password -->
            <div class="mt-4">
                <x-auth.input-label for="password" :value="__('Contraseña')" />
                <x-auth.text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <x-auth.input-error :messages="$errors->get('password')" class="mt-2 text-blue-500 dark:text-blue-300" />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" name="remember"
                        class="rounded border-gray-400 dark:border-gray-500 text-blue-600 dark:text-blue-400 bg-white dark:bg-[#1B263B] focus:ring-[#1E40AF]">
                    <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Recordarme') }}</span>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between mt-6">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-blue-600 dark:text-blue-300 hover:text-blue-800 dark:hover:text-blue-400 transition"
                        href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif

                <x-auth.primary-button
                    class="ms-3 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white dark:bg-[#A9D6E5] dark:text-[#0D1B2A] dark:hover:bg-[#89C2D9]">
                    {{ __('Iniciar sesión') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
