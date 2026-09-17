<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dentamor') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased min-h-screen flex items-center justify-center relative overflow-hidden bg-background text-on-surface p-4 sm:p-6">
        <div class="fixed inset-0 pointer-events-none opacity-[0.03]" style="background-image: radial-gradient(#00C4A7 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-primary/20 rounded-full blur-3xl -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-secondary/20 rounded-full blur-3xl -ml-20 -mb-20"></div>

        <div class="relative w-full max-w-md">
            <div class="mb-8 text-center flex flex-col items-center">
                <x-logo :size="72" :href="url('/')" :show-text="false" class="mb-3" />
                <h1 class="font-headline-md text-headline-md font-extrabold">
                    <span class="text-primary-dark">Dent</span><span class="text-primary">amor</span>
                </h1>
                <p class="text-body-sm text-on-surface-variant mt-1">Facturación electrónica para consultorios dentales</p>
            </div>

            <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-lg">
                {{ $slot }}
            </div>

            <p class="text-center text-label-sm text-on-surface-variant mt-6">SISDENT · SUNAT Facturación Electrónica</p>
        </div>
    </body>
</html>