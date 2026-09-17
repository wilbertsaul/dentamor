<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Dentamor') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-surface-container-low text-on-surface">
        @include('layouts.navigation')

        <div class="md:ml-sidebar pt-16 min-h-screen pb-24 md:pb-0">
            <div class="p-gutter">
                @if (session('message'))
                    <div class="mb-6 px-4 py-3 rounded-xl flex items-center gap-2 border border-primary/20" style="background-color: #E6FAF7; color: #00796B;">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        {{ session('message') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-6 px-4 py-3 rounded-xl flex items-center gap-2 border border-error/20" style="background-color: #FEE2E2; color: #B91C1C;">
                        <span class="material-symbols-outlined text-lg">error</span>
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>

        <div class="fixed inset-0 pointer-events-none opacity-[0.025] z-[-1]" style="background-image: radial-gradient(#00C4A7 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>

        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('openPdf', (payload) => {
                    const args = Array.isArray(payload) ? payload[0] : payload;
                    const { url, redirect } = args || {};
                    const win = window.__livewirePdfWin;
                    if (win && !win.closed) {
                        win.location.href = url;
                    } else if (url) {
                        window.open(url, '_blank');
                    }
                    if (redirect) {
                        setTimeout(() => {
                            window.location.href = redirect;
                        }, 1500);
                    }
                });
            });
        </script>
    </body>
</html>
