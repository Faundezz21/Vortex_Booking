<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'Reserva salas de estudio'])
        <meta name="description" content="Reserva salas de estudio en bloques de 30 minutos, con calendario, check-in por QR y lista de espera.">
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <header class="border-b border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" aria-label="Vortex Booking"><x-app-logo /></a>
                <nav class="flex items-center gap-2">
                    <x-theme-toggle class="mr-1 hidden sm:inline-flex" />
                    @auth
                        <flux:button variant="primary" size="sm" :href="route('dashboard')">Ir a la app</flux:button>
                    @else
                        <flux:button variant="ghost" size="sm" :href="route('login')">Iniciar sesión</flux:button>
                        <flux:button variant="primary" size="sm" :href="route('register')">Crear cuenta</flux:button>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-4 pb-12 pt-16 sm:px-6 lg:pt-24">
                <div class="max-w-2xl">
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Reserva una sala de estudio en segundos.</h1>
                    <p class="mt-5 text-lg text-zinc-600 dark:text-zinc-400">
                        Mira la disponibilidad de todas las salas en un calendario, reserva bloques de 30 minutos y registra tu llegada con el QR de la puerta.
                        Si alguien no llega, la sala se libera sola y avisamos a quien estaba esperando.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <flux:button variant="primary" :href="route('calendar')" icon-trailing="arrow-right">Abrir el calendario</flux:button>
                        @else
                            <flux:button variant="primary" :href="route('register')" icon-trailing="arrow-right">Crear cuenta gratis</flux:button>
                            <flux:button :href="route('login')">Ver la demo</flux:button>
                        @endauth
                    </div>
                </div>

                {{-- Ocupación real de hoy --}}
                <div class="mt-14 overflow-hidden rounded-2xl border border-zinc-200 shadow-sm dark:border-zinc-800">
                    <div class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                            Ocupación de hoy · {{ ucfirst(now(config('app.display_timezone'))->translatedFormat('l j \d\e F')) }}
                        </div>
                        <span class="text-xs text-zinc-500">Datos en vivo</span>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="min-w-[44rem] p-4">
                            <div class="mb-2 ml-44 flex justify-between text-[11px] tabular-nums text-zinc-400">
                                @foreach ($hours as $h)<span>{{ sprintf('%02d:00', $h) }}</span>@endforeach
                            </div>
                            <div class="space-y-2">
                                @foreach ($timeline as $row)
                                    <div class="flex items-center gap-4">
                                        <div class="flex w-40 shrink-0 items-center gap-2 truncate text-sm">
                                            <span class="size-2 shrink-0 rounded-full {{ $row['room']->color->dot() }}"></span>
                                            <span class="truncate">{{ $row['room']->name }}</span>
                                        </div>
                                        <div class="relative h-7 flex-1 rounded-md bg-zinc-100 dark:bg-zinc-900">
                                            @foreach ($row['bars'] as $bar)
                                                <div class="absolute inset-y-1 rounded {{ $row['room']->color->dot() }} opacity-85"
                                                     style="left: {{ $bar['left'] }}%; width: {{ $bar['width'] }}%" title="{{ $bar['label'] }}"></div>
                                            @endforeach
                                            @if ($nowLeft !== null)
                                                <div class="absolute -inset-y-1 w-0.5 bg-red-500" style="left: {{ $nowLeft }}%"></div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="mx-auto grid max-w-6xl gap-x-10 gap-y-8 px-4 py-16 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
                    @foreach ([
                        ['calendar-days', 'Calendario semanal y diario', 'Todas las salas lado a lado, con un color por sala. Haz clic en un espacio libre y reserva.'],
                        ['qr-code', 'Check-in con QR', 'Cada puerta tiene su código. Si nadie llega en 15 minutos, la sala se libera automáticamente.'],
                        ['bell', 'Lista de espera', '¿Bloque ocupado? Pide que te avisemos. Si se libera, te llega un correo para reservarlo.'],
                        ['arrow-path', 'Sincroniza tu calendario', 'Suscríbete desde Google Calendar u Outlook y tus reservas aparecen solas.'],
                    ] as [$icon, $title, $text])
                        <div>
                            <div class="grid size-9 place-items-center rounded-lg bg-white text-brand ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                                <flux:icon :name="$icon" variant="outline" class="size-5" />
                            </div>
                            <h2 class="mt-4 text-sm font-semibold">{{ $title }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </main>

        <footer class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex max-w-6xl flex-wrap justify-between gap-2 px-4 py-6 text-xs text-zinc-500 sm:px-6">
                <span>Vortex Booking</span>
                <span>Laravel 13 · Livewire 4 · PostgreSQL</span>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
