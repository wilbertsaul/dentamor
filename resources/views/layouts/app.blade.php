<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'IziPay') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-surface text-on-surface">
        @include('layouts.navigation')

        <div class="md:ml-sidebar pt-16 min-h-screen pb-24 md:pb-0">
            <div class="p-gutter">
                @if (session('message'))
                    <div class="mb-6 px-4 py-3 rounded-xl flex items-center gap-2" style="background-color: #E3FCEF; color: #006644;">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        {{ session('message') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-6 px-4 py-3 rounded-xl flex items-center gap-2" style="background-color: #FFEBE6; color: #BF2600;">
                        <span class="material-symbols-outlined text-lg">error</span>
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>

        <div class="fixed inset-0 pointer-events-none opacity-[0.03] z-[-1]" style="background-image: radial-gradient(#003d9b 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>
    </body>
</html>
