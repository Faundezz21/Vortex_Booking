<div class="grid gap-8 md:grid-cols-[12rem_1fr]">
    <nav class="flex gap-1 overflow-x-auto md:flex-col" aria-label="Configuración">
        @foreach ([
            ['settings.profile', 'Perfil', 'user-circle'],
            ['settings.password', 'Contraseña', 'key'],
            ['settings.appearance', 'Apariencia', 'sun'],
            ['settings.calendar', 'Calendario', 'calendar'],
        ] as [$route, $label, $icon])
            <x-nav-item :href="route($route)" :icon="$icon" :active="request()->routeIs($route)">{{ $label }}</x-nav-item>
        @endforeach
    </nav>

    <div class="max-w-xl">
        <h2 class="text-lg font-semibold">{{ $heading ?? '' }}</h2>
        <p class="mt-1 text-sm text-zinc-500">{{ $subheading ?? '' }}</p>

        <div class="mt-6">
            {{ $slot }}
        </div>
    </div>
</div>
