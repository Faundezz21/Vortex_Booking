<x-layouts.app title="Inicio">
    <x-page-header :title="'Hola, '.str(auth()->user()->name)->before(' ')" :subtitle="ucfirst(now(config('app.display_timezone'))->translatedFormat('l j \d\e F'))">
        <x-slot:actions>
            <flux:button icon="calendar-days" :href="route('calendar')" wire:navigate>Calendario</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('rooms.index')" wire:navigate>Nueva reserva</flux:button>
        </x-slot:actions>
    </x-page-header>

    @if ($current)
        <div class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/40">
            <div class="grid size-10 place-items-center rounded-lg bg-brand text-white"><flux:icon.qr-code /></div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold">
                    {{ $current->hasStarted() ? 'Tu reserva ya comenzó' : 'Tu reserva empieza pronto' }}: {{ $current->room->name }}, {{ $current->timeRangeLabel() }}
                </div>
                <div class="text-sm text-zinc-600 dark:text-zinc-300">
                    Escanea el QR de la puerta para registrar tu llegada antes de las {{ $current->checkInDeadline()->setTimezone(config('app.display_timezone'))->format('H:i') }} o la sala se liberará.
                </div>
            </div>
            <flux:button size="sm" :href="route('reservations.show', $current)" wire:navigate>Ver reserva</flux:button>
        </div>
    @endif

    @isset($stats)
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500">{{ $stat['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $stat['value'] }}</div>
                    @isset($stat['hint'])<div class="mt-0.5 text-xs text-zinc-400">{{ $stat['hint'] }}</div>@endisset
                </div>
            @endforeach
        </div>
    @endisset

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <section class="rounded-xl border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <h2 class="text-sm font-semibold">Tus próximas reservas</h2>
                <span class="text-xs text-zinc-500">{{ $upcoming->count() }} de {{ $maxActive }} activas</span>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($upcoming as $reservation)
                    <x-reservation-row :reservation="$reservation" />
                @empty
                    <div class="px-4 py-10 text-center">
                        <flux:icon.calendar class="mx-auto mb-2 text-zinc-300" />
                        <p class="text-sm font-medium">Sin reservas próximas</p>
                        <p class="mt-1 text-sm text-zinc-500">Busca un espacio libre en el calendario.</p>
                    </div>
                @endforelse
            </div>
            @if ($waitlist->isNotEmpty())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <div class="mb-2 flex items-center gap-1.5 text-xs font-medium text-zinc-500"><flux:icon.bell variant="micro" /> En lista de espera</div>
                    <ul class="space-y-1 text-sm">
                        @foreach ($waitlist as $entry)
                            <li class="flex items-center gap-2">
                                <span class="size-2 rounded-full {{ $entry->room->color->dot() }}"></span>
                                {{ $entry->room->name }} ·
                                <span class="tabular-nums text-zinc-500">{{ ucfirst($entry->starts_at->setTimezone(config('app.display_timezone'))->translatedFormat('D j M, H:i')) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <h2 class="text-sm font-semibold">Disponibilidad hoy</h2>
                <a href="{{ route('rooms.index') }}" wire:navigate class="text-xs font-medium text-brand hover:underline">Ver todas</a>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($rooms as $room)
                    <x-room-row :room="$room" compact />
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
