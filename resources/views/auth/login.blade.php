<x-guest-layout>
    <div class="flex items-center gap-2 mb-6">
        <span class="material-symbols-outlined text-primary">lock</span>
        <h2 class="text-headline-sm font-bold text-on-surface">Iniciar sesión</h2>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@sisdent.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-outline-variant text-primary shadow-sm focus:ring-primary" name="remember">
                <span class="ms-2 text-body-sm text-on-surface-variant">Recuérdame</span>
            </label>
        </div>

        <div class="flex items-center justify-between pt-2">
            @if (Route::has('password.request'))
                <a class="text-body-sm text-primary hover:text-primary-700 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <x-primary-button class="!px-6">
                Ingresar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>