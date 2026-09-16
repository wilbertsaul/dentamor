<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dentamor') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased min-h-screen flex items-center justify-center relative overflow-hidden bg-surface-container-low text-on-surface p-4 sm:p-6">
        <div class="fixed inset-0 pointer-events-none opacity-[0.04]" style="background-image: radial-gradient(#003d9b 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-primary-fixed/50 rounded-full blur-3xl -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-secondary-fixed/40 rounded-full blur-3xl -ml-20 -mb-20"></div>

        <div class="relative w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="/" class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary text-on-primary shadow-md mb-3">
                    <span class="material-symbols-outlined text-3xl">health_and_safety</span>
                </a>
                <h1 class="text-headline-md font-bold text-on-surface">{{ config('app.name', 'Dentamor') }}</h1>
                <p class="text-body-sm text-on-surface-variant mt-1">Facturación electrónica para consultorios dentales</p>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-lg">
                {{ $slot }}
            </div>

            <p class="text-center text-label-sm text-on-surface-variant mt-6">SISDENT · SUNAT Facturación Electrónica</p>
        </div>
    </body>
</html>