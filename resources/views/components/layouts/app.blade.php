<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100" x-data="{ nav: false }">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-40 flex w-60 print:hidden -translate-x-full flex-col border-r border-zinc-200 bg-zinc-50 transition-transform lg:translate-x-0 dark:border-zinc-800 dark:bg-zinc-900"
               :class="nav && 'translate-x-0'" aria-label="Navegación">
            <div class="flex h-14 items-center px-4">
                <a href="{{ route('dashboard') }}" wire:navigate><x-app-logo /></a>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2">
                <div class="space-y-0.5">
                    <x-nav-item :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">Inicio</x-nav-item>
                    <x-nav-item :href="route('calendar')" icon="calendar-days" :active="request()->routeIs('calendar')">Calendario</x-nav-item>
                    <x-nav-item :href="route('rooms.index')" icon="building-library" :active="request()->routeIs('rooms.*')">Salas</x-nav-item>
                    <x-nav-item :href="route('reservations.index')" icon="ticket" :active="request()->routeIs('reservations.*')">Mis reservas</x-nav-item>
                </div>

                @can('admin')
                    <div class="space-y-0.5">
                        <div class="px-2 pb-1.5 text-xs font-medium text-zinc-400 dark:text-zinc-500">Administración</div>
                        <x-nav-item :href="route('admin.rooms.index')" icon="squares-2x2" :active="request()->routeIs('admin.rooms.*')">Gestionar salas</x-nav-item>
                        <x-nav-item :href="route('admin.reservations.index')" icon="queue-list" :active="request()->routeIs('admin.reservations.*')">Todas las reservas</x-nav-item>
                    </div>
                @endcan
            </nav>

            <div class="border-t border-zinc-200 p-3 dark:border-zinc-800">
                <div class="mb-2 flex items-center justify-between px-1.5">
                    <span class="text-xs text-zinc-500">Tema</span>
                    <x-theme-toggle />
                </div>
                <flux:dropdown position="top" align="start" class="w-full">
                    <button type="button" class="flex w-full items-center gap-2.5 rounded-md p-1.5 text-left hover:bg-zinc-200/60 dark:hover:bg-zinc-800">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-soft text-xs font-semibold text-brand">{{ auth()->user()->initials() }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-zinc-500">{{ auth()->user()->role->label() }}</span>
                        </span>
                        <flux:icon.chevron-up-down variant="micro" class="text-zinc-400" />
                    </button>
                    <x-user-menu />
                </flux:dropdown>
            </div>
        </aside>

        {{-- Fondo al abrir el menú en móvil --}}
        <div x-show="nav" x-transition.opacity @click="nav = false" class="fixed inset-0 z-30 bg-zinc-900/30 lg:hidden" x-cloak></div>

        <div class="lg:pl-60 print:pl-0">
            {{-- Barra superior en móvil --}}
            <div class="sticky top-0 z-20 flex h-14 items-center gap-3 print:hidden border-b border-zinc-200 bg-white/90 px-4 backdrop-blur lg:hidden dark:border-zinc-800 dark:bg-zinc-950/90">
                <button type="button" @click="nav = true" class="-ml-1 rounded-md p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Abrir menú">
                    <flux:icon.bars-3 class="size-5" />
                </button>
                <x-app-logo />
                <x-theme-toggle class="ml-auto" />
            </div>

            <main class="px-4 py-6 sm:px-8 sm:py-8">
                <x-flash />
                {{ $slot }}
            </main>
        </div>

        @fluxScripts
    </body>
</html>
