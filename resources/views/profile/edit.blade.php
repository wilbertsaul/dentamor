<x-app-layout>
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-headline-md text-on-surface">Mi Perfil</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Administra tu información de cuenta y contraseña.</p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>