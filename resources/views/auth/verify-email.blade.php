<x-guest-layout>
    <h2 class="text-headline-sm font-bold text-on-surface mb-3">Verifica tu correo</h2>
    <div class="mb-4 text-body-sm text-on-surface-variant">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-semibold text-body-sm text-secondary">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
               class="underline text-body-sm text-on-surface-variant hover:text-on-surface rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                {{ __('Log Out') }}
            </a>
        </form>
    </div>
</x-guest-layout>