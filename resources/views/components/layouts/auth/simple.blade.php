<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <x-theme-toggle class="absolute right-4 top-4" />
        <div class="flex min-h-svh flex-col items-center justify-center px-4 py-10">
            <a href="{{ route('home') }}" wire:navigate class="mb-8" aria-label="Vortex Booking"><x-app-logo /></a>
            <div class="w-full max-w-sm rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
            <p class="mt-6 text-xs text-zinc-400">Reservas de salas de estudio</p>
        </div>
        @fluxScripts
    </body>
</html>
